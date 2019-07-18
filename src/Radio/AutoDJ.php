<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\GetNextSong;
use App\Radio\Backend\Liquidsoap;
use Azura\Cache;
use Azura\EventDispatcher;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutoDJ implements EventSubscriberInterface
{
    /** @var int The time to live (in seconds) of cached playlist queues. */
    public const CACHE_TTL = 43200;

    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcher */
    protected $dispatcher;

    /** @var Filesystem */
    protected $filesystem;

    /** @var Logger */
    protected $logger;

    /** @var Cache */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param EventDispatcher $dispatcher
     * @param Filesystem $filesystem
     * @param Logger $logger
     * @param Cache $cache
     *
     * @see \App\Provider\RadioProvider
     */
    public function __construct(
        EntityManager $em,
        EventDispatcher $dispatcher,
        Filesystem $filesystem,
        Logger $logger,
        Cache $cache)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->cache = $cache;
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

                $playlist = $sh->getPlaylist();

                if ($playlist instanceof Entity\StationPlaylist) {
                    $event->addAnnotations([
                        'playlist_id' => $playlist->getId(),
                    ]);

                    // Handle "Jingle mode" by sending the same metadata as the previous song.
                    if ($playlist->isJingle()) {
                        $np = $event->getStation()->getNowplaying();
                        if ($np instanceof Entity\Api\NowPlaying) {
                            $event->addAnnotations([
                                'title' => $np->now_playing->song->title,
                                'artist' => $np->now_playing->song->artist,
                                'playlist_id' => null,
                                'media_id' => null,
                                'jingle_mode' => 'true',
                            ]);
                        }
                    }
                }
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
        $next_song = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s, sp, sm
            FROM App\Entity\SongHistory sh
            LEFT JOIN sh.song s 
            LEFT JOIN sh.media sm
            LEFT JOIN sh.playlist sp
            WHERE sh.station_id = :station_id
            AND sh.timestamp_cued != 0
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $event->getStation()->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($next_song instanceof Entity\SongHistory) {
            $this->logger->debug('Database has a next song already registered.');
            $event->setNextSong($next_song);
        }
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param GetNextSong $event
     */
    public function calculateNextSong(GetNextSong $event): void
    {
        $station = $event->getStation();
        $now = Chronos::now(new \DateTimeZone($station->getTimezone()));

        $song_history_count = 15;

        // Pull all active, non-empty playlists and sort by type.
        $playlists_by_type = [];
        foreach($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if ($playlist->isPlayable()) {
                $type = $playlist->getType();

                if (Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS === $type) {
                    $song_history_count = max($song_history_count, $playlist->getPlayPerSongs());
                }

                $playlists_by_type[$type][$playlist->getId()] = $playlist;
            }
        }

        // Pull all recent cued songs for easy referencing below.
        $cued_song_history = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s 
            FROM App\Entity\SongHistory sh JOIN sh.song s  
            WHERE sh.station_id = :station_id
            AND (sh.timestamp_cued != 0 AND sh.timestamp_cued IS NOT NULL)
            AND sh.timestamp_cued >= :threshold
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', time()-86399)
            ->setMaxResults($song_history_count)
            ->getArrayResult();

        // Types of playlists that should play, sorted by priority.
        $typesToPlay = [
            Entity\StationPlaylist::TYPE_ONCE_PER_HOUR,
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
                if ($playlist->shouldPlayNow($now, $cued_song_history)) {
                    $eligible_playlists[$playlist_id] = $playlist->getWeight();
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

                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist, $cued_song_history))) {
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
     * @param array $recent_song_history
     * @return Entity\SongHistory|string|null
     */
    protected function _playSongFromPlaylist(Entity\StationPlaylist $playlist, array $recent_song_history)
    {
        /** @var Entity\Repository\SongRepository $song_repo */
        $song_repo = $this->em->getRepository(Entity\Song::class);

        $media_to_play = $this->_getQueuedSong($playlist, $recent_song_history);

        if ($media_to_play instanceof Entity\StationMedia) {
            $spm = $media_to_play->getItemForPlaylist($playlist);
            $spm->played();

            $this->em->persist($spm);

            // Log in history
            $sh = new Entity\SongHistory($media_to_play->getSong(), $playlist->getStation());
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);
            $sh->setTimestampCued(time());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        if (is_array($media_to_play)) {
            [$media_uri, $media_duration] = $media_to_play;

            $sh = new Entity\SongHistory($song_repo->getOrCreate([
                'text' => 'Remote Playlist URL',
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

    /**
     * @param Entity\StationPlaylist $playlist
     * @param array $recent_song_history
     * @return Entity\StationMedia|array|null
     */
    protected function _getQueuedSong(Entity\StationPlaylist $playlist, array $recent_song_history)
    {
        if (Entity\StationPlaylist::SOURCE_REMOTE_URL === $playlist->getSource()) {
            return $this->_playRemoteUrl($playlist);
        }

        /** @var Entity\Repository\StationPlaylistMediaRepository $spm_repo */
        $spm_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);

        if (Entity\StationPlaylist::ORDER_RANDOM === $playlist->getOrder()) {
            $media_queue = $spm_repo->getPlayableMedia($playlist);

            shuffle($media_queue);

            [$media_id, $media_queue] = $this->_preventDuplicates($media_queue, $recent_song_history);
        } else {
            $cache_name = self::getPlaylistCacheName($playlist->getId());
            $media_queue = (array)$this->cache->get($cache_name);

            if (empty($media_queue)) {
                $media_queue = $spm_repo->getPlayableMedia($playlist);
            }

            [$media_id, $media_queue] = $this->_preventDuplicates($media_queue, $recent_song_history);

            // Save the modified cache, sans the now-missing entry.
            $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);
        }

        return ($media_id)
            ? $this->em->find(Entity\StationMedia::class, $media_id)
            : null;
    }

    /**
     * @param array $eligible_media
     * @param array $played_media
     * @return array
     */
    protected function _preventDuplicates(array $eligible_media = [], array $played_media = []): array
    {
        $media_id_to_play = null;
        $artists = [];
        $titles = [];
        foreach($played_media as $history) {
            $artists[] = $history['song']['artist'];
            $titles[] = $history['song']['title'];
        }

        foreach($eligible_media as $media_id => $media) {
            if (!in_array($media['artist'], $artists, true) && !in_array($media['title'], $titles, true)) {
                $media_id_to_play = $media_id;
                unset($eligible_media[$media_id]);
                break;
            }
        }

        if (null === $media_id_to_play) {
            $media = array_shift($eligible_media);
            $media_id_to_play = $media['id'];
        }

        return [
            $media_id_to_play,
            $eligible_media
        ];
    }

    protected function _playRemoteUrl(Entity\StationPlaylist $playlist): ?array
    {
        $remote_type = $playlist->getRemoteType() ?? Entity\StationPlaylist::REMOTE_TYPE_STREAM;

        // Handle a raw stream URL of possibly indeterminate length.
        if (Entity\StationPlaylist::REMOTE_TYPE_STREAM === $remote_type) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            if (Entity\StationPlaylist::TYPE_SCHEDULED === $playlist->getType()) {
                $duration = $playlist->getScheduleDuration();
                return [$playlist->getRemoteUrl(), $duration];
            }

            return [$playlist->getRemoteUrl(), 0];
        }

        // Handle a remote playlist containing songs or streams.
        $cache_name = self::getPlaylistCacheName($playlist->getId());
        $media_queue = (array)$this->cache->get($cache_name);

        if (empty($media_queue)) {
            $playlist_raw = file_get_contents($playlist->getRemoteUrl());
            $media_queue = PlaylistParser::getSongs($playlist_raw);
        }

        if (!empty($media_queue)) {
            $media_id = array_shift($media_queue);
        } else {
            $media_id = null;
        }

        // Save the modified cache, sans the now-missing entry.
        $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);

        return ($media_id) ? [$media_id, 0] : null;
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
            $request = $this->em->createQuery(/** @lang DQL */ 'SELECT sr, sm 
                FROM App\Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0 
                AND sr.station_id = :station_id 
                AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
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

    public static function getPlaylistCacheName(int $playlist_id): string
    {
        return 'autodj/playlist_'.$playlist_id;
    }
}
