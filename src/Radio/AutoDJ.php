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

            // Mark the playlist itself as played at this time.
            $playlist = $next_song->getPlaylist();
            if ($playlist instanceof Entity\StationPlaylist) {
                $playlist->played();
                $this->em->persist($playlist);
            }

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
        $this->logger->info('AzuraCast AutoDJ is calculating the next song to play...');

        $station = $event->getStation();
        $now = Chronos::now(new \DateTimeZone($station->getTimezone()));

        $song_history_count = 15;

        // Pull all active, non-empty playlists and sort by type.
        $has_any_valid_playlists = false;
        $playlists_by_type = [];
        foreach($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if ($playlist->isPlayable()) {
                $has_any_valid_playlists = true;
                $type = $playlist->getType();

                if (Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS === $type) {
                    $song_history_count = max($song_history_count, $playlist->getPlayPerSongs());
                }

                $playlists_by_type[$type][$playlist->getId()] = $playlist;
            }
        }

        if (!$has_any_valid_playlists) {
            $this->logger->error('No valid playlists detected. Skipping AutoDJ calculations.');
            return;
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

            $this->logger->info(sprintf('Playable playlists of type "%s" found.', $type), $eligible_playlists);

            // Shuffle playlists by weight.
            $rand = random_int(1, (int)array_sum($eligible_playlists));
            foreach ($eligible_playlists as $playlist_id => $weight) {
                $rand -= $weight;
                if ($rand <= 0) {
                    $playlist = $playlists_by_type[$type][$playlist_id];

                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist, $cued_song_history))) {
                        $this->logger->info('Playable track found and registered.');
                        return;
                    }
                }
            }
        }

        $this->logger->error('No playable tracks were found.');
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

        switch($playlist->getOrder()) {
            case Entity\StationPlaylist::ORDER_RANDOM:
                $media_queue = $spm_repo->getPlayableMedia($playlist);

                shuffle($media_queue);

                $media_id = $this->_preventDuplicates($media_queue, $recent_song_history);
                break;

            case Entity\StationPlaylist::ORDER_SEQUENTIAL:
                $cache_name = self::getPlaylistCacheName($playlist->getId());
                $media_queue = (array)$this->cache->get($cache_name);

                if (empty($media_queue)) {
                    $media_queue = $spm_repo->getPlayableMedia($playlist);
                }

                $media_arr = array_shift($media_queue);
                $media_id = $media_arr['id'];

                // Save the modified cache, sans the now-missing entry.
                $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);
                break;

            case Entity\StationPlaylist::ORDER_SHUFFLE:
            default:
                $cache_name = self::getPlaylistCacheName($playlist->getId());
                $media_queue_cached = (array)$this->cache->get($cache_name);

                if (empty($media_queue_cached)) {
                    $media_queue = $spm_repo->getPlayableMedia($playlist);
                } else {
                    // Rekey the media queue because redis won't always properly store keys.
                    $media_queue = [];
                    foreach($media_queue_cached as $media) {
                        $media_queue[$media['id']] = $media;
                    }
                }

                $media_id = $this->_preventDuplicates($media_queue, $recent_song_history, false);

                if (null === $media_id) {
                    $this->logger->warn('Duplicate prevention yielded no playable song; resetting song queue.');

                    // Pull the entire shuffled playlist if a duplicate title can't be avoided.
                    $media_queue = $spm_repo->getPlayableMedia($playlist);
                    $media_id = $this->_preventDuplicates($media_queue, $recent_song_history, true);
                }

                if (null !== $media_id) {
                    unset($media_queue[$media_id]);
                }

                // Save the modified cache, sans the now-missing entry.
                $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);
                break;
        }

        if (!$media_id) {
            $this->logger->error(sprintf('Playlist "%s" has no playable tracks.', $playlist->getName()), [
                'playlist_id' => $playlist->getId(),
                'playlist_order' => $playlist->getOrder(),
            ]);
            return null;
        }

        return $this->em->find(Entity\StationMedia::class, $media_id);
    }

    /**
     * @param array $eligible_media
     * @param array $played_media
     * @param bool $accept_duplicate Whether to return a media ID even if duplicates can't be prevented.
     * @return int|null
     */
    protected function _preventDuplicates(array $eligible_media = [], array $played_media = [], $accept_duplicate = true): ?int
    {
        if (empty($eligible_media)) {
            $this->logger->debug('Eligible song queue is empty!');
            return null;
        }

        $artists = [];
        $latest_song_ids_played = [];

        foreach($played_media as $history) {
            $artist = trim($history['song']['artist']);
            if (!empty($artist)) {
                $artists[$artist] = $artist;
            }

            $song_id = $history['song']['id'];
            if (!isset($latest_song_ids_played[$song_id])) {
                $latest_song_ids_played[$song_id] = $history['timestamp_cued'];
            }
        }

        $without_same_title = [];

        foreach($eligible_media as $media) {
            $song_id = $media['song_id'];
            if (isset($latest_song_ids_played[$song_id])) {
                continue;
            }

            $artist = trim($media['artist']);
            if (empty($artist) || !isset($artists[$artist])) {
                $media_id_to_play = $media['id'];
                $this->logger->info('Found track that avoids title and artist match!', ['media_id' => $media_id_to_play]);
                return $media_id_to_play;
            }

            $without_same_title[] = $media;
        }

        // If we reach this point, there was no match for avoiding same artist AND title.
        if (!empty($without_same_title)) {
            $media = reset($without_same_title);
            $media_id_to_play = $media['id'];

            $this->logger->info('Cannot avoid artist match; defaulting to title match.', ['media_id' => $media_id_to_play]);
            return $media_id_to_play;
        }

        if ($accept_duplicate) {

            // If we reach this point, there's no way to avoid a duplicate title.
            $media_ids_by_time_played = [];

            // For each piece of eligible media, get its latest played timestamp.
            foreach($eligible_media as $media) {
                $song_id = $media['song_id'];
                $media_ids_by_time_played[$media['id']] = $latest_song_ids_played[$song_id] ?? 0;
            }

            // Pull the lowest value, which corresponds to the least recently played song.
            asort($media_ids_by_time_played);

            // More efficient way of getting first key.
            foreach($media_ids_by_time_played as $media_id_to_play => $unused) {
                $this->logger->warning('No way to avoid same title OR same artist; using least recently played song.', ['media_id' => $media_id_to_play]);
                return $media_id_to_play;
            }
        }

        return null;
    }

    protected function _playRemoteUrl(Entity\StationPlaylist $playlist): ?array
    {
        $remote_type = $playlist->getRemoteType() ?? Entity\StationPlaylist::REMOTE_TYPE_STREAM;

        // Handle a raw stream URL of possibly indeterminate length.
        if (Entity\StationPlaylist::REMOTE_TYPE_STREAM === $remote_type) {
            // Temporarily disable remote URL streams being served via the Azura-AutoDJ.
            // TODO: Evaluate a potential fix for this.
            return null;

            /*
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            if (Entity\StationPlaylist::TYPE_SCHEDULED === $playlist->getType()) {
                $duration = $playlist->getScheduleDuration();
                return [$playlist->getRemoteUrl(), $duration];
            }

            return [$playlist->getRemoteUrl(), 0];
            */
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

        return ($media_id)
            ? [$media_id, 0]
            : null;
    }

    /**
     * @param GetNextSong $event
     */
    public function getNextSongFromRequests(GetNextSong $event): void
    {
        $station = $event->getStation();

        if (!$station->getEnableRequests()) {
            return;
        }

        $min_minutes = (int)$station->getRequestDelay();
        $threshold_minutes = $min_minutes + random_int(0, $min_minutes);

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

            $event->setNextSong($sh);
        }
    }

    public static function getPlaylistCacheName(int $playlist_id): string
    {
        return 'autodj/playlist_'.$playlist_id;
    }
}
