<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\BuildQueue;
use App\EventDispatcher;
use App\Lock\LockManager;
use Cake\Chronos\Chronos;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutoDJ implements EventSubscriberInterface
{
    public const DEFAULT_QUEUE_LENGTH = 3;

    protected Adapters $adapters;

    protected EntityManager $em;

    protected Entity\Repository\SongRepository $songRepo;

    protected Entity\Repository\SongHistoryRepository $songHistoryRepo;

    protected Entity\Repository\StationPlaylistMediaRepository $spmRepo;

    protected Entity\Repository\StationStreamerRepository $streamerRepo;

    protected Entity\Repository\StationRequestRepository $requestRepo;

    protected EventDispatcher $dispatcher;

    protected Filesystem $filesystem;

    protected Logger $logger;

    protected LockManager $lockManager;

    public function __construct(
        Adapters $adapters,
        EntityManager $em,
        Entity\Repository\SongRepository $songRepo,
        Entity\Repository\SongHistoryRepository $songHistoryRepo,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        Entity\Repository\StationStreamerRepository $streamerRepo,
        Entity\Repository\StationRequestRepository $requestRepo,
        EventDispatcher $dispatcher,
        Filesystem $filesystem,
        Logger $logger,
        LockManager $lockManager
    ) {
        $this->adapters = $adapters;
        $this->em = $em;
        $this->songRepo = $songRepo;
        $this->songHistoryRepo = $songHistoryRepo;
        $this->spmRepo = $spmRepo;
        $this->streamerRepo = $streamerRepo;
        $this->requestRepo = $requestRepo;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->lockManager = $lockManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AnnotateNextSong::class => [
                ['defaultAnnotationHandler', 0],
            ],
            BuildQueue::class => [
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
     *
     * @return string
     */
    public function annotateNextSong(Entity\Station $station, $as_autodj = false): string
    {
        $sh = $this->songHistoryRepo->getNextInQueue($station);

        $event = new AnnotateNextSong($station, $sh);
        $this->dispatcher->dispatch($event);

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
            $sh->sentToAutodj();
            $this->em->persist($sh);

            // The "get next song" function is only called when a streamer is not live
            $this->streamerRepo->onDisconnect($event->getStation());
            $this->em->flush();

            $media = $sh->getMedia();
            if ($media instanceof Entity\StationMedia) {
                $fs = $this->filesystem->getForStation($event->getStation());
                $media_path = $fs->getFullPath($media->getPathUri());

                $event->setSongPath($media_path);

                $backend = $this->adapters->getBackendAdapter($event->getStation());
                $event->addAnnotations($backend->annotateMedia($media));

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
            } elseif (!empty($sh->getAutodjCustomUri())) {
                $custom_uri = $sh->getAutodjCustomUri();

                $event->setSongPath($custom_uri);
                if ($sh->getDuration()) {
                    $event->addAnnotations([
                        'length' => $sh->getDuration(),
                    ]);
                }
            }
        } elseif (null !== $sh) {
            $event->setSongPath((string)$sh);
        }
    }

    public function buildQueue(Entity\Station $station): void
    {
        $this->logger->pushProcessor(function ($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName(),
            ];
            return $record;
        });

        // Determine the "now" time for the queue.
        $stationTz = new DateTimeZone($station->getTimezone());

        $currentSong = $this->songHistoryRepo->getCurrent($station);
        if ($currentSong instanceof Entity\SongHistory) {
            $nowTimestamp = $currentSong->getTimestampStart() + ($currentSong->getDuration() ?? 0);
            $now = Chronos::createFromTimestamp($nowTimestamp, $stationTz);
        } else {
            $now = Chronos::now($stationTz);
        }

        // Adjust "now" time from current queue.
        $backendOptions = $station->getBackendConfig();
        $maxQueueLength = (int)($backendOptions['autodj_queue_length'] ?? self::DEFAULT_QUEUE_LENGTH);

        $upcomingQueue = $this->songHistoryRepo->getUpcomingQueue($station);
        $queueLength = count($upcomingQueue);

        if ($queueLength >= $maxQueueLength) {
            $this->logger->debug('AutoDJ queue is already at current max length (' . $maxQueueLength . ').');
            $this->logger->popProcessor();
            return;
        }

        foreach ($upcomingQueue as $queueRow) {
            $duration = $queueRow->getDuration() ?? 0;
            $now = $now->addSeconds($duration);
        }

        // Build the remainder of the queue.
        while ($queueLength < $maxQueueLength) {

            $event = new BuildQueue($station, $now);
            $this->dispatcher->dispatch($event);

            $queueRow = $event->getNextSong();
            if ($queueRow instanceof Entity\SongHistory) {
                $duration = $queueRow->getDuration() ?? 0;
                $now = $now->addSeconds($duration);
            }

            $queueLength++;
        }

        $this->logger->popProcessor();
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param BuildQueue $event
     */
    public function calculateNextSong(BuildQueue $event): void
    {
        $this->logger->info('AzuraCast AutoDJ is calculating the next song to play...');

        $station = $event->getStation();
        $now = $event->getNow();

        $song_history_count = 15;

        // Pull all active, non-empty playlists and sort by type.
        $has_any_valid_playlists = false;
        $playlists_by_type = [];
        foreach ($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if ($playlist->isPlayable()) {
                $has_any_valid_playlists = true;
                $type = $playlist->getType();

                if (Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS === $type) {
                    $song_history_count = max($song_history_count, $playlist->getPlayPerSongs());
                }

                $subType = ($playlist->getScheduleItems()->count() > 0) ? 'scheduled' : 'unscheduled';
                $playlists_by_type[$type . '_' . $subType][$playlist->getId()] = $playlist;
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
            ->setParameter('threshold', $now->subDay()->getTimestamp())
            ->setMaxResults($song_history_count)
            ->getArrayResult();

        // Types of playlists that should play, sorted by priority.
        $typesToPlay = [
            Entity\StationPlaylist::TYPE_ONCE_PER_HOUR . '_scheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_HOUR . '_unscheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS . '_scheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS . '_unscheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES . '_scheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES . '_unscheduled',
            Entity\StationPlaylist::TYPE_DEFAULT . '_scheduled',
            Entity\StationPlaylist::TYPE_DEFAULT . '_unscheduled',
        ];

        foreach ($typesToPlay as $type) {
            if (empty($playlists_by_type[$type])) {
                continue;
            }

            $log_playlists = [];
            $eligible_playlists = [];
            foreach ($playlists_by_type[$type] as $playlist_id => $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($playlist->shouldPlayNow($now, $cued_song_history)) {
                    $eligible_playlists[$playlist_id] = $playlist->getWeight();
                    $log_playlists[] = [
                        'id' => $playlist->getId(),
                        'name' => $playlist->getName(),
                        'weight' => $playlist->getWeight(),
                    ];
                }
            }

            if (empty($eligible_playlists)) {
                continue;
            }

            $this->logger->info(sprintf(
                '%d playable playlist(s) of type "%s" found.',
                count($eligible_playlists),
                $type
            ), ['playlists' => $log_playlists]);

            // Shuffle playlists by weight.
            $this->weightedShuffle($eligible_playlists);

            // Loop through the playlists and attempt to play them with no duplicates first,
            // then loop through them again while allowing duplicates.
            foreach ([false, true] as $allowDuplicates) {
                foreach ($eligible_playlists as $playlist_id => $weight) {
                    $playlist = $playlists_by_type[$type][$playlist_id];

                    if ($event->setNextSong($this->playSongFromPlaylist(
                        $playlist,
                        $cued_song_history,
                        $now,
                        $allowDuplicates
                    ))) {
                        $this->logger->info('Playable track found and registered.', [
                            'next_song' => (string)$event,
                        ]);
                        return;
                    }
                }
            }
        }

        $this->logger->error('No playable tracks were found.');
    }

    /**
     * Apply a weighted shuffle to the given array in the form:
     *  [ key1 => weight1, key2 => weight2 ]
     *
     * Based on: https://gist.github.com/savvot/e684551953a1716208fbda6c4bb2f344
     *
     * @param array $array
     */
    protected function weightedShuffle(array &$array): void
    {
        $arr = $array;

        $max = 1.0 / mt_getrandmax();
        array_walk($arr, function (&$v, $k) use ($max) {
            $v = (mt_rand() * $max) ** (1.0 / $v);
        });
        arsort($arr);
        array_walk($arr, function (&$v, $k) use ($array) {
            $v = $array[$k];
        });

        $array = $arr;
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param Chronos $now
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     *
     * @return Entity\SongHistory|null
     */
    protected function playSongFromPlaylist(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        Chronos $now,
        bool $allowDuplicates = false
    ): ?Entity\SongHistory {
        $media_to_play = $this->getQueuedSong($playlist, $recentSongHistory, $allowDuplicates);

        if ($media_to_play instanceof Entity\StationMedia) {
            $playlist->setPlayedAt($now->getTimestamp());
            $this->em->persist($playlist);

            $spm = $media_to_play->getItemForPlaylist($playlist);
            $spm->played($now->getTimestamp());

            $this->em->persist($spm);

            // Log in history
            $sh = new Entity\SongHistory($media_to_play->getSong(), $playlist->getStation());
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);
            $sh->setTimestampCued($now->getTimestamp());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        if (is_array($media_to_play)) {
            [$media_uri, $media_duration] = $media_to_play;

            $playlist->setPlayedAt($now->getTimestamp());
            $this->em->persist($playlist);

            $sh = new Entity\SongHistory($this->songRepo->getOrCreate([
                'text' => 'Remote Playlist URL',
            ]), $playlist->getStation());

            $sh->setPlaylist($playlist);
            $sh->setAutodjCustomUri($media_uri);
            $sh->setDuration($media_duration);
            $sh->setTimestampCued($now->getTimestamp());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        return null;
    }

    /**
     * @param Entity\StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     *
     * @return Entity\StationMedia|array|null
     */
    protected function getQueuedSong(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates = false
    ) {
        if (Entity\StationPlaylist::SOURCE_REMOTE_URL === $playlist->getSource()) {
            return $this->playRemoteUrl($playlist);
        }

        switch ($playlist->getOrder()) {
            case Entity\StationPlaylist::ORDER_RANDOM:
                $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, $allowDuplicates);
                break;

            case Entity\StationPlaylist::ORDER_SEQUENTIAL:
                $mediaQueue = $playlist->getQueue();

                if (empty($mediaQueue)) {
                    $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                }

                $media_arr = array_shift($mediaQueue);
                $mediaId = $media_arr['id'];

                $playlist->setQueue($mediaQueue);
                $this->em->persist($playlist);
                break;

            case Entity\StationPlaylist::ORDER_SHUFFLE:
            default:
                $media_queue_cached = $playlist->getQueue();

                if (empty($media_queue_cached)) {
                    $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                } else {
                    // Rekey the media queue because redis won't always properly store keys.
                    $mediaQueue = [];
                    foreach ($media_queue_cached as $media) {
                        $mediaQueue[$media['id']] = $media;
                    }
                }

                if ($allowDuplicates) {
                    $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, false);

                    if (null === $mediaId) {
                        $this->logger->warning('Duplicate prevention yielded no playable song; resetting song queue.');

                        // Pull the entire shuffled playlist if a duplicate title can't be avoided.
                        $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                        $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, true);
                    }
                } else {
                    $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, false);
                }

                if (null !== $mediaId) {
                    unset($mediaQueue[$mediaId]);
                }

                // Save the modified cache, sans the now-missing entry.
                $playlist->setQueue($mediaQueue);
                $this->em->persist($playlist);
                break;
        }

        $this->em->flush($playlist);

        if (!$mediaId) {
            $this->logger->error(sprintf('Playlist "%s" did not return a playable track.', $playlist->getName()), [
                'playlist_id' => $playlist->getId(),
                'playlist_order' => $playlist->getOrder(),
                'allow_duplicates' => $allowDuplicates,
            ]);
            return null;
        }

        return $this->em->find(Entity\StationMedia::class, $mediaId);
    }

    protected function playRemoteUrl(Entity\StationPlaylist $playlist): ?array
    {
        $remote_type = $playlist->getRemoteType() ?? Entity\StationPlaylist::REMOTE_TYPE_STREAM;

        // Handle a raw stream URL of possibly indeterminate length.
        if (Entity\StationPlaylist::REMOTE_TYPE_STREAM === $remote_type) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            $duration = $playlist->getScheduleDuration();
            return [$playlist->getRemoteUrl(), $duration];
        }

        // Handle a remote playlist containing songs or streams.
        $media_queue = $playlist->getQueue();

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
        $playlist->setQueue($media_queue);
        $this->em->persist($playlist);
        $this->em->flush($playlist);

        return ($media_id)
            ? [$media_id, 0]
            : null;
    }

    /**
     * @param array $eligibleMedia
     * @param array $playedMedia
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     *
     * @return int|null
     */
    protected function preventDuplicates(
        array $eligibleMedia = [],
        array $playedMedia = [],
        bool $allowDuplicates = false
    ): ?int {
        if (empty($eligibleMedia)) {
            $this->logger->debug('Eligible song queue is empty!');
            return null;
        }

        $latestSongIdsPlayed = [];
        $playedTracks = [];

        foreach ($playedMedia as $history) {
            $playedTracks[] = [
                'artist' => $history['song']['artist'],
                'title' => $history['song']['title'],
            ];

            $songId = $history['song']['id'];

            if (!isset($latestSongIdsPlayed[$songId])) {
                $latestSongIdsPlayed[$songId] = $history['timestamp_cued'];
            }
        }

        $eligibleTracks = [];

        foreach ($eligibleMedia as $media) {
            $songId = $media['song_id'];
            if (isset($latestSongIdsPlayed[$songId])) {
                continue;
            }

            $eligibleTracks[$media['id']] = [
                'artist' => $media['song']['artist'],
                'title' => $media['song']['title'],
            ];
        }

        $mediaId = self::getDistinctTrack($eligibleTracks, $playedTracks);

        if (null !== $mediaId) {
            $this->logger->info('Found track that avoids duplicate title and artist.',
                ['media_id' => $mediaId]);

            return $mediaId;
        }

        if ($allowDuplicates) {

            // If we reach this point, there's no way to avoid a duplicate title.
            $mediaIdsByTimePlayed = [];

            // For each piece of eligible media, get its latest played timestamp.
            foreach ($eligibleMedia as $media) {
                $songId = $media['song_id'];
                $mediaIdsByTimePlayed[$media['id']] = $latestSongIdsPlayed[$songId] ?? 0;
            }

            // Pull the lowest value, which corresponds to the least recently played song.
            asort($mediaIdsByTimePlayed);

            // More efficient way of getting first key.
            foreach ($mediaIdsByTimePlayed as $mediaId => $unused) {
                $this->logger->warning('No way to avoid same title OR same artist; using least recently played song.',
                    ['media_id' => $mediaId]);

                return $mediaId;
            }
        }

        return null;
    }

    /**
     * @param BuildQueue $event
     */
    public function getNextSongFromRequests(BuildQueue $event): void
    {
        $now = $event->getNow();

        $request = $this->requestRepo->getNextPlayableRequest($event->getStation());
        if (null === $request) {
            return;
        }

        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

        // Log in history
        $sh = new Entity\SongHistory($request->getTrack()->getSong(), $request->getStation());
        $sh->setRequest($request);
        $sh->setMedia($request->getTrack());

        $sh->setDuration($request->getTrack()->getCalculatedLength());
        $sh->setTimestampCued($now->getTimestamp());
        $this->em->persist($sh);

        $request->setPlayedAt($now->getTimestamp());
        $this->em->persist($request);

        $this->em->flush();

        $event->setNextSong($sh);
    }

    /**
     * Given an array of eligible tracks, return the first ID that doesn't have a duplicate artist/
     *   title with any of the previously played tracks.
     *
     * Both should be in the form of an array, i.e.:
     *  [ 'id' => ['artist' => 'Foo', 'title' => 'Fighters'] ]
     *
     * @param array $eligibleTracks
     * @param array $playedTracks
     *
     * @return int|string|null
     */
    public static function getDistinctTrack(array $eligibleTracks, array $playedTracks)
    {
        $artists = [];
        $titles = [];

        foreach ($playedTracks as $song) {
            $title = trim($song['title']);
            $titles[$title] = $title;

            $artistParts = explode(',', $song['artist']);
            foreach ($artistParts as $artist) {
                $artist = trim($artist);
                if (!empty($artist)) {
                    $artists[$artist] = $artist;
                }
            }
        }

        $eligibleTracksWithoutSameTitle = [];

        foreach ($eligibleTracks as $trackId => $song) {
            // Avoid all direct title matches.
            $title = trim($song['title']);

            if (isset($titles[$title])) {
                continue;
            }

            // Attempt to avoid an artist match, if possible.
            $artist = trim($song['artist']);

            $artistMatchFound = false;
            if (!empty($artist)) {
                $artistParts = explode(',', $artist);
                foreach ($artistParts as $artist) {
                    $artist = trim($artist);
                    if (empty($artist)) {
                        continue;
                    }

                    if (isset($artists[$artist])) {
                        $artistMatchFound = true;
                        break;
                    }
                }
            }

            if (!$artistMatchFound) {
                return $trackId;
            }

            $eligibleTracksWithoutSameTitle[$trackId] = $song;
        }

        foreach ($eligibleTracksWithoutSameTitle as $trackId => $song) {
            return $trackId;
        }

        return null;
    }
}
