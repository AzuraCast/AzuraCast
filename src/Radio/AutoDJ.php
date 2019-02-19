<?php
namespace App\Radio;

use App\Entity;
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

    /** @var Logger */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param EventDispatcher $dispatcher
     * @param Logger $logger
     *
     * @see \App\Provider\RadioProvider
     */
    public function __construct(
        EntityManager $em,
        EventDispatcher $dispatcher,
        Logger $logger)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            GetNextSong::NAME => [
                ['checkDatabaseForNextSong', 10],
                ['getNextSongFromRequests', 5],
                ['calculateNextSong', 0],
            ],
        ];
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
            $sh = new Entity\SongHistory($media_to_play->getSong(), $playlist->getStation());
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);

            $sh->setDuration($media_to_play->getCalculatedLength());
            $sh->setTimestampCued(time());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        if (is_array($media_to_play)) {
            [$media_uri, $media_duration] = $media_to_play;

            $sh = new Entity\SongHistory($song_repo->getOrCreate([
                'text' => 'Internal AutoDJ URI',
            ]), $playlist->getStation());

            $sh->setPlaylist($playlist);
            $sh->setAutodjCustomUri($media_uri);
            $sh->setDuration($media_duration);

            $sh->setTimestampCued(time());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        return null;
    }
}
