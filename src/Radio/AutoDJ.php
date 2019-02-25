<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\GetNextSong;
use App\Radio\Backend\Liquidsoap;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutoDJ implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcher */
    protected $dispatcher;

    /** @var Filesystem */
    protected $filesystem;

    /** @var Logger */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param EventDispatcher $dispatcher
     * @param Filesystem $filesystem
     * @param Logger $logger
     *
     * @see \App\Provider\RadioProvider
     */
    public function __construct(
        EntityManager $em,
        EventDispatcher $dispatcher,
        Filesystem $filesystem,
        Logger $logger)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AnnotateNextSong::NAME => [
                ['defaultAnnotationHandler', 0],
            ],
            GetNextSong::NAME => [
                ['checkDatabaseForNextSong', 10],
                ['getNextSongFromRequests', 5],
                ['calculateNextSong', 0],
            ],
        ];
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     *
     * @param Entity\Station $station
     * @param bool $as_autodj
     * @return string
     */
    public function annotateNextSong(Entity\Station $station, $as_autodj = false): string
    {
        /** @var Entity\SongHistory|string|null $sh */
        $sh = $this->getNextSong($station, $as_autodj);

        $event = new AnnotateNextSong($station, $sh);
        $this->dispatcher->dispatch(AnnotateNextSong::NAME, $event);

        return $event->buildAnnotations();
    }

    /**
     * Event Handler function for the AnnotateNextSong event.
     *
     * @param AnnotateNextSong $event
     */
    public function defaultAnnotationHandler(AnnotateNextSong $event): void
    {
        $sh = $event->getNextSong();

        if ($sh instanceof Entity\SongHistory) {
            $media = $sh->getMedia();
            if ($media instanceof Entity\StationMedia) {
                $fs = $this->filesystem->getForStation($event->getStation());
                $media_path = $fs->getFullPath($media->getPathUri());

                $event->setSongPath($media_path);
                $event->addAnnotations($media->getAnnotations());
            } else if (!empty($sh->getAutodjCustomUri())) {
                $custom_uri = $sh->getAutodjCustomUri();

                $event->setSongPath($custom_uri);
                if ($sh->getDuration()) {
                    $event->addAnnotations([
                        'length' => $sh->getDuration(),
                    ]);
                }
            }
        } else if (null !== $sh) {
            $event->setSongPath((string)$sh);
        } else {
            $error_file = APP_INSIDE_DOCKER
                ? '/usr/local/share/icecast/web/error.mp3'
                : APP_INCLUDE_ROOT . '/resources/error.mp3';

            $event->setSongPath($error_file);
        }
    }

    /**
     * If the next song for a station has already been calculated, return the calculated result; otherwise,
     * calculate the next playing song.
     * 
     * @param Entity\Station $station
     * @param bool $is_autodj
     * @return Entity\SongHistory|null
     */
    public function getNextSong(Entity\Station $station, $is_autodj = false): ?Entity\SongHistory
    {
        if ($station->useManualAutoDJ()) {
            return null;
        }

        $this->logger->pushProcessor(function($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName(),
            ];
            return $record;
        });

        $event = new GetNextSong($station);
        $this->dispatcher->dispatch(GetNextSong::NAME, $event);

        $this->logger->popProcessor();

        $next_song = $event->getNextSong();

        if ($next_song instanceof Entity\SongHistory && $is_autodj) {
            $next_song->sentToAutodj();
            $this->em->persist($next_song);

            // The "get next song" function is only called when a streamer is not live
            $station->setIsStreamerLive(false);
            $this->em->persist($station);

            $this->em->flush();
        }

        return $next_song;
    }

    public function checkDatabaseForNextSong(GetNextSong $event): void
    {
        $nextSongQuery = /** @lang DQL */ 'SELECT sh, s, sp, sm
            FROM App\Entity\SongHistory sh
            LEFT JOIN sh.song s 
            LEFT JOIN sh.media sm
            LEFT JOIN sh.playlist sp
            WHERE sh.station_id = :station_id
            AND sh.timestamp_cued != 0
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.id DESC';

        $next_song = $this->em->createQuery($nextSongQuery)
            ->setParameter('station_id', $event->getStation()->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($next_song instanceof Entity\SongHistory) {
            $this->logger->debug('Database has a next song already registered.');
            $event->setNextSong($next_song);
        }
    }

    /**
     * @param GetNextSong $event
     */
    public function getNextSongFromRequests(GetNextSong $event): void
    {
        $station = $event->getStation();

        // Process requests first (if applicable)
        if ($station->getEnableRequests()) {

            $min_minutes = (int)$station->getRequestDelay();
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $requestQuery = /** @lang DQL */ 'SELECT sr, sm 
                FROM App\Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0 
                AND sr.station_id = :station_id 
                AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC';

            $request = $this->em->createQuery($requestQuery)
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $threshold)
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($request instanceof Entity\StationRequest) {
                $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

                $event->setNextSong($this->_playSongFromRequest($request));
            }
        }
    }

    /**
     * Given a StationRequest object, create a new SongHistory entry that cues the requested song to play next.
     *
     * @param Entity\StationRequest $request
     * @return Entity\SongHistory
     */
    protected function _playSongFromRequest(Entity\StationRequest $request): Entity\SongHistory
    {
        // Log in history
        $sh = new Entity\SongHistory($request->getTrack()->getSong(), $request->getStation());
        $sh->setRequest($request);
        $sh->setMedia($request->getTrack());

        $sh->setDuration($request->getTrack()->getCalculatedLength());
        $sh->setTimestampCued(time());
        $this->em->persist($sh);

        $request->setPlayedAt(time());
        $this->em->persist($request);

        $this->em->flush();

        return $sh;
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param GetNextSong $event
     */
    public function calculateNextSong(GetNextSong $event): void
    {
        $station = $event->getStation();

        $songHistoryCount = 15;

        // Pull all active, non-empty playlists and sort by type.
        $playlists_by_type = [];
        foreach($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if ($playlist->isPlayable()) {
                $type = $playlist->getType();

                if (Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS === $type) {
                    $songHistoryCount = max($songHistoryCount, $playlist->getPlayPerSongs());
                }

                $playlists_by_type[$type][$playlist->getId()] = $playlist;
            }
        }

        // Pull all recent cued songs for easy referencing below.
        $cuedSongHistoryQuery = /** @lang DQL */ 'SELECT sh 
            FROM App\Entity\SongHistory sh
            WHERE sh.station_id = :station_id
            AND (sh.timestamp_cued != 0 AND sh.timestamp_cued IS NOT NULL)
            AND sh.timestamp_cued >= :threshold
            ORDER BY sh.timestamp_cued DESC';

        $cued_song_history = $this->em->createQuery($cuedSongHistoryQuery)
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', time()-86399)
            ->getArrayResult();

        // Types of playlists that should play, sorted by priority.
        $typesToPlay = [
            Entity\StationPlaylist::TYPE_ONCE_PER_DAY,
            Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS,
            Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES,
            Entity\StationPlaylist::TYPE_SCHEDULED,
            Entity\StationPlaylist::TYPE_DEFAULT,
        ];

        foreach($typesToPlay as $type) {
            if (empty($playlists_by_type[$type])) {
                continue;
            }

            $eligible_playlists = [];
            foreach($playlists_by_type[$type] as $playlist_id => $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($playlist->canPlay($cued_song_history)) {
                    $eligible_playlists[$playlist_id] = $playlist->getCalculatedWeight();
                }
            }

            if (empty($eligible_playlists)) {
                continue;
            }

            $this->logger->debug(sprintf('Playable playlists of type "%s" found.', $type), $eligible_playlists);

            // Shuffle playlists by weight.
            $rand = random_int(1, (int)array_sum($eligible_playlists));
            foreach ($eligible_playlists as $playlist_id => $weight) {
                $rand -= $weight;
                if ($rand <= 0) {
                    $playlist = $playlists_by_type[$type][$playlist_id];

                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @return Entity\SongHistory|string|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _playSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        /** @var Entity\Repository\StationPlaylistMediaRepository $spm_repo */
        $spm_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);

        /** @var Entity\Repository\SongRepository $song_repo */
        $song_repo = $this->em->getRepository(Entity\Song::class);

        $media_to_play = $spm_repo->getQueuedSong($playlist);

        if ($media_to_play instanceof Entity\StationMedia) {
            $spm = $media_to_play->getItemForPlaylist($playlist);
            $spm->played();

            $this->em->persist($spm);

            // Log in history
            return $this->setNextCuedSong(
                $playlist->getStation(),
                $media_to_play->getSong(),
                $media_to_play,
                $playlist
            );
        }

        if (is_array($media_to_play)) {
            [$media_uri, $media_duration] = $media_to_play;

            return $this->setNextCuedSong(
                $playlist->getStation(),
                $song_repo->getOrCreate(['text' => 'Internal AutoDJ URI']),
                null,
                $playlist,
                $media_duration,
                $media_uri
            );
        }

        return null;
    }

    /**
     * @param Entity\Station $station
     * @param Entity\Song|string $song
     * @param Entity\StationMedia|string|int|null $media
     * @param Entity\StationPlaylist|string|int|null $playlist
     * @param int|null $duration
     * @param string|null $custom_uri
     * @return Entity\SongHistory
     */
    public function setNextCuedSong(
        Entity\Station $station,
        $song,
        $media = null,
        $playlist = null,
        $duration = null,
        $custom_uri = null): Entity\SongHistory
    {
        /** @var Entity\Song|null $song */
        $song = $this->getEntity(Entity\Song::class, $song);

        if (!($song instanceof Entity\Song)) {
            throw new \Azura\Exception('Error: Song ID is not valid.');
        }

        /** @var Entity\Repository\SongHistoryRepository $sh_repo */
        $sh_repo = $this->em->getRepository(Entity\SongHistory::class);
        $sh = $sh_repo->getCuedSong($song, $station);

        if ($sh instanceof Entity\SongHistory) {
            return $sh;
        }

        $sh = new Entity\SongHistory($song, $station);
        $sh->setTimestampCued(time());

        $media = $this->getEntity(Entity\StationMedia::class, $media);
        if ($media instanceof Entity\StationMedia) {
            $sh->setMedia($media);
        }

        $playlist = $this->getEntity(Entity\StationPlaylist::class, $playlist);
        if ($playlist instanceof Entity\StationPlaylist) {
            $sh->setPlaylist($playlist);
        }

        if (!empty($duration)) {
            $sh->setDuration($duration);
        } else if ($media instanceof Entity\StationMedia) {
            $sh->setDuration($media->getCalculatedLength());
        }

        if (!empty($custom_uri)) {
            $sh->setAutodjCustomUri($custom_uri);
        }

        $this->em->persist($sh);
        $this->em->flush($sh);

        return $sh;
    }

    /**
     * Fetch an entity if given either the entity object itself OR its identifier.
     *
     * @param string $class_name
     * @param object|string|int $identifier
     * @return object|null
     */
    protected function getEntity($class_name, $identifier): ?object
    {
        if ($identifier instanceof $class_name) {
            return $identifier;
        }

        if (empty($identifier)) {
            return null;
        }

        return $this->em->find($class_name, $identifier);
    }
}
