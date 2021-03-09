<?php

namespace App\Radio\AutoDJ;

use App\Entity;
use App\Event\Radio\BuildQueue;
use App\Radio\PlaylistParser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Queue implements EventSubscriberInterface
{
    protected const TYPES_TO_PLAY_BY_PRIORITY = [
        Entity\StationPlaylist::TYPE_ONCE_PER_HOUR . '_scheduled',
        Entity\StationPlaylist::TYPE_ONCE_PER_HOUR . '_unscheduled',
        Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS . '_scheduled',
        Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS . '_unscheduled',
        Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES . '_scheduled',
        Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES . '_unscheduled',
        Entity\StationPlaylist::TYPE_DEFAULT . '_scheduled',
        Entity\StationPlaylist::TYPE_DEFAULT . '_unscheduled',
    ];

    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;

    protected Scheduler $scheduler;

    protected Entity\Repository\StationPlaylistMediaRepository $spmRepo;

    protected Entity\Repository\StationRequestRepository $requestRepo;

    protected Entity\Repository\SongHistoryRepository $historyRepo;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Scheduler $scheduler,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        Entity\Repository\StationRequestRepository $requestRepo,
        Entity\Repository\SongHistoryRepository $historyRepo
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        $this->spmRepo = $spmRepo;
        $this->requestRepo = $requestRepo;
        $this->historyRepo = $historyRepo;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BuildQueue::class => [
                ['getNextSongFromRequests', 5],
                ['calculateNextSong', 0],
            ],
        ];
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

        [$activePlaylistsByType, $oncePerXSongHistoryCount] = $this->getActivePlaylistsSortedByType($station);

        if (empty($activePlaylistsByType)) {
            $this->logger->error('No valid playlists detected. Skipping AutoDJ calculations.');
            return;
        }

        $recentSongHistoryForOncePerXSongs = $this->historyRepo->getRecentlyPlayed(
            $station,
            $now,
            $oncePerXSongHistoryCount
        );

        $recentSongHistoryForDuplicatePrevention = $this->historyRepo->getRecentlyPlayedByTimeRange(
            $station,
            $now,
            $station->getBackendConfig()->getDuplicatePreventionTimeRange()
        );

        $this->logRecentSongHistory(
            $now,
            $recentSongHistoryForOncePerXSongs,
            $recentSongHistoryForDuplicatePrevention
        );

        foreach (self::TYPES_TO_PLAY_BY_PRIORITY as $currentPlaylistType) {
            if (empty($activePlaylistsByType[$currentPlaylistType])) {
                continue;
            }

            [$eligiblePlaylists, $logPlaylists] = $this->filterEligiblePlaylists(
                $activePlaylistsByType,
                $currentPlaylistType,
                $now,
                $recentSongHistoryForOncePerXSongs
            );

            if (empty($eligiblePlaylists)) {
                continue;
            }

            $this->logPlayablePlaylists(
                $currentPlaylistType,
                $eligiblePlaylists,
                $logPlaylists
            );

            $this->weightedShuffle($eligiblePlaylists);

            // Loop through the playlists and attempt to play them with no duplicates first,
            // then loop through them again while allowing duplicates.
            foreach ([false, true] as $allowDuplicates) {
                $nextSong = $this->playNextSongFromEligiblePlaylists(
                    $currentPlaylistType,
                    $eligiblePlaylists,
                    $activePlaylistsByType,
                    $recentSongHistoryForDuplicatePrevention,
                    $now,
                    $allowDuplicates
                );

                if ($event->setNextSong($nextSong)) {
                    $this->logger->info(
                        'Playable track found and registered.',
                        [
                            'next_song' => (string)$event,
                        ]
                    );
                    return;
                }
            }
        }

        $this->logger->error('No playable tracks were found.');
    }

    /**
     * Returns an array containing an array of  the active playlists sorted by playlist type
     * and the maximum amount of songs that need to be fetched from the song history for
     * playlists of type "once per x songs"
     *
     * @return mixed[]
     */
    protected function getActivePlaylistsSortedByType(Entity\Station $station): array
    {
        $playlistsByType = [];
        $oncePerXSongHistoryCount = 15;

        foreach ($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if ($playlist->isPlayable()) {
                $type = $playlist->getType();

                if (Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS === $type) {
                    $oncePerXSongHistoryCount = max($oncePerXSongHistoryCount, $playlist->getPlayPerSongs());
                }

                $subType = ($playlist->getScheduleItems()->count() > 0) ? 'scheduled' : 'unscheduled';
                $playlistsByType[$type . '_' . $subType][$playlist->getId()] = $playlist;
            }
        }

        return [$playlistsByType, $oncePerXSongHistoryCount];
    }

    /**
     * Filters the supplied array of playlists to return ony the ones that should play now.
     * Returns an array with an array of the filtered playlists and an array containing detailed
     * information about each eligible playlist for logging purposes.
     *
     * @return mixed[]
     */
    protected function filterEligiblePlaylists(
        array $playlistsByType,
        string $type,
        CarbonInterface $now,
        array $recentSongHistoryForOncePerXSongs
    ): array {
        $eligiblePlaylists = [];
        $logPlaylists = [];

        foreach ($playlistsByType[$type] as $playlistId => $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if (!$this->scheduler->shouldPlaylistPlayNow($playlist, $now, $recentSongHistoryForOncePerXSongs)) {
                continue;
            }

            $eligiblePlaylists[$playlistId] = $playlist->getWeight();

            $logPlaylists[] = [
                'id' => $playlist->getId(),
                'name' => $playlist->getName(),
                'weight' => $playlist->getWeight(),
            ];
        }

        return [$eligiblePlaylists, $logPlaylists];
    }

    /**
     * Apply a weighted shuffle to the given array in the form:
     *  [ key1 => weight1, key2 => weight2 ]
     *
     * Based on: https://gist.github.com/savvot/e684551953a1716208fbda6c4bb2f344
     *
     * @param array $original
     */
    protected function weightedShuffle(array &$original): void
    {
        $new = $original;

        $max = 1.0 / mt_getrandmax();

        array_walk(
            $new,
            function (&$value, $key) use ($max): void {
                $value = (mt_rand() * $max) ** (1.0 / $value);
            }
        );

        arsort($new);

        array_walk(
            $new,
            function (&$value, $key) use ($original): void {
                $value = $original[$key];
            }
        );

        $original = $new;
    }

    protected function playNextSongFromEligiblePlaylists(
        string $currentPlaylistType,
        array $eligiblePlaylists,
        array $activePlaylistsByType,
        array $recentSongHistoryForDuplicatePrevention,
        CarbonInterface $now,
        bool $allowDuplicates
    ): ?Entity\StationQueue {
        foreach ($eligiblePlaylists as $playlistId => $weight) {
            $playlist = $activePlaylistsByType[$currentPlaylistType][$playlistId];

            $nextSong = $this->playSongFromPlaylist(
                $playlist,
                $recentSongHistoryForDuplicatePrevention,
                $now,
                $allowDuplicates
            );

            if ($nextSong !== null) {
                return $nextSong;
            }
        }

        return null;
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param CarbonInterface $now
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     */
    protected function playSongFromPlaylist(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        CarbonInterface $now,
        bool $allowDuplicates = false
    ): ?Entity\StationQueue {
        $mediaToPlay = $this->getQueuedSong($playlist, $recentSongHistory, $allowDuplicates);

        if ($mediaToPlay instanceof Entity\StationMedia) {
            $playlist->setPlayedAt($now->getTimestamp());
            $this->em->persist($playlist);

            $spm = $mediaToPlay->getItemForPlaylist($playlist);
            if ($spm instanceof Entity\StationPlaylistMedia) {
                $spm->played($now->getTimestamp());
                $this->em->persist($spm);
            }

            $stationQueueEntry = new Entity\StationQueue($playlist->getStation(), $mediaToPlay);
            $stationQueueEntry->setPlaylist($playlist);
            $stationQueueEntry->setMedia($mediaToPlay);
            $stationQueueEntry->setTimestampCued($now->getTimestamp());

            $this->em->persist($stationQueueEntry);
            $this->em->flush();

            return $stationQueueEntry;
        }

        if (is_array($mediaToPlay)) {
            [$mediaUri, $mediaDuration] = $mediaToPlay;

            $playlist->setPlayedAt($now->getTimestamp());
            $this->em->persist($playlist);

            $stationQueueEntry = new Entity\StationQueue(
                $playlist->getStation(),
                Entity\Song::createFromText('Remote Playlist URL')
            );

            $stationQueueEntry->setPlaylist($playlist);
            $stationQueueEntry->setAutodjCustomUri($mediaUri);
            $stationQueueEntry->setDuration($mediaDuration);
            $stationQueueEntry->setTimestampCued($now->getTimestamp());

            $this->em->persist($stationQueueEntry);
            $this->em->flush();

            return $stationQueueEntry;
        }

        return null;
    }

    /**
     * @param Entity\StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     *
     * @return Entity\StationMedia|mixed[]|null
     */
    protected function getQueuedSong(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates = false
    ) {
        if (Entity\StationPlaylist::SOURCE_REMOTE_URL === $playlist->getSource()) {
            return $this->getMediaFromRemoteUrl($playlist);
        }

        $mediaId = null;

        switch ($playlist->getOrder()) {
            case Entity\StationPlaylist::ORDER_RANDOM:
                $mediaId = $this->getRandomMediaIdFromPlaylist(
                    $playlist,
                    $recentSongHistory,
                    $allowDuplicates
                );
                break;

            case Entity\StationPlaylist::ORDER_SEQUENTIAL:
                $mediaId = $this->getSequentialMediaIdFromPlaylist($playlist);
                break;

            case Entity\StationPlaylist::ORDER_SHUFFLE:
            default:
                $mediaId = $this->getShuffledMediaIdFromPlaylist(
                    $playlist,
                    $recentSongHistory,
                    $allowDuplicates
                );
                break;
        }

        $this->em->flush();

        if (!$mediaId) {
            $this->logger->warning(
                sprintf('Playlist "%s" did not return a playable track.', $playlist->getName()),
                [
                    'playlist_id' => $playlist->getId(),
                    'playlist_order' => $playlist->getOrder(),
                    'allow_duplicates' => $allowDuplicates,
                ]
            );
            return null;
        }

        return $this->em->find(Entity\StationMedia::class, $mediaId);
    }

    /**
     * Returns either an array containing the URL of a remote stream and the duration,
     * an array with a media id and the duration or null if no media has been found.
     *
     * @return mixed[]|null
     */
    protected function getMediaFromRemoteUrl(Entity\StationPlaylist $playlist): ?array
    {
        $remoteType = $playlist->getRemoteType() ?? Entity\StationPlaylist::REMOTE_TYPE_STREAM;

        // Handle a raw stream URL of possibly indeterminate length.
        if (Entity\StationPlaylist::REMOTE_TYPE_STREAM === $remoteType) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            $duration = $this->scheduler->getPlaylistScheduleDuration($playlist);
            return [$playlist->getRemoteUrl(), $duration];
        }

        // Handle a remote playlist containing songs or streams.
        $mediaQueue = $playlist->getQueue();

        if (empty($mediaQueue)) {
            $playlistRaw = file_get_contents($playlist->getRemoteUrl());
            $mediaQueue = PlaylistParser::getSongs($playlistRaw);
        }

        $mediaId = null;

        if (!empty($mediaQueue)) {
            $mediaId = array_shift($mediaQueue);
        }

        // Save the modified cache, sans the now-missing entry.
        $playlist->setQueue($mediaQueue);
        $this->em->persist($playlist);
        $this->em->flush();

        return ($mediaId)
            ? [$mediaId, 0]
            : null;
    }

    protected function getRandomMediaIdFromPlaylist(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates
    ): ?int {
        $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);

        if ($playlist->getAvoidDuplicates()) {
            return $this->preventDuplicates($mediaQueue, $recentSongHistory, $allowDuplicates);
        }

        $mediaId = array_key_first($mediaQueue);

        return $mediaId;
    }

    protected function getSequentialMediaIdFromPlaylist(Entity\StationPlaylist $playlist): ?int
    {
        $mediaQueue = $playlist->getQueue();

        if (empty($mediaQueue)) {
            $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
        }

        $nextMediaArray = array_shift($mediaQueue);
        $mediaId = $nextMediaArray['id'];

        $playlist->setQueue($mediaQueue);
        $this->em->persist($playlist);

        return $mediaId;
    }

    protected function getShuffledMediaIdFromPlaylist(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates
    ): ?int {
        $mediaId = null;
        $mediaQueue = $playlist->getQueue();

        if (empty($mediaQueue)) {
            $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
        }

        if ($playlist->getAvoidDuplicates()) {
            if ($allowDuplicates) {
                $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, false);

                if (null === $mediaId) {
                    $this->logger->warning(
                        'Duplicate prevention yielded no playable song; resetting song queue.'
                    );

                    // Pull the entire shuffled playlist if a duplicate title can't be avoided.
                    $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                    $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, true);
                }
            } else {
                $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, false);
            }
        } else {
            $mediaId = array_key_first($mediaQueue);
        }

        if (null !== $mediaId) {
            unset($mediaQueue[$mediaId]);
        }

        // Save the modified cache, sans the now-missing entry.
        $playlist->setQueue($mediaQueue);
        $this->em->persist($playlist);

        return $mediaId;
    }

    /**
     * @param array $eligibleTracks
     * @param array $playedTracks
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     */
    protected function preventDuplicates(
        array $eligibleTracks = [],
        array $playedTracks = [],
        bool $allowDuplicates = false
    ): ?int {
        if (empty($eligibleTracks)) {
            $this->logger->debug('Eligible song queue is empty!');
            return null;
        }

        $latestSongIdsPlayed = [];

        foreach ($playedTracks as $playedTrack) {
            $songId = $playedTrack['song_id'];

            if (!isset($latestSongIdsPlayed[$songId])) {
                $latestSongIdsPlayed[$songId] = $playedTrack['timestamp_cued'] ?? $playedTrack['timestamp_start'];
            }
        }

        $notPlayedEligibleTracks = [];

        foreach ($eligibleTracks as $mediaId => $track) {
            $songId = $track['song_id'];
            if (isset($latestSongIdsPlayed[$songId])) {
                continue;
            }

            $notPlayedEligibleTracks[$mediaId] = $track;
        }

        $mediaId = self::getDistinctTrack($notPlayedEligibleTracks, $playedTracks);

        if (null !== $mediaId) {
            $this->logger->info(
                'Found track that avoids duplicate title and artist.',
                ['media_id' => $mediaId]
            );

            return $mediaId;
        }

        if ($allowDuplicates) {
            // If we reach this point, there's no way to avoid a duplicate title.
            $mediaIdsByTimePlayed = [];

            // For each piece of eligible media, get its latest played timestamp.
            foreach ($eligibleTracks as $track) {
                $songId = $track['song_id'];
                $mediaIdsByTimePlayed[$track['id']] = $latestSongIdsPlayed[$songId] ?? 0;
            }

            // Pull the lowest value, which corresponds to the least recently played song.
            asort($mediaIdsByTimePlayed);

            $mediaId = array_key_first($mediaIdsByTimePlayed);
            if (null !== $mediaId) {
                $this->logger->warning(
                    'No way to avoid same title OR same artist; using least recently played song.',
                    ['media_id' => $mediaId]
                );

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

        $request = $this->requestRepo->getNextPlayableRequest($event->getStation(), $now);
        if (null === $request) {
            return;
        }

        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

        $stationQueueEntry = new Entity\StationQueue($request->getStation(), $request->getTrack());
        $stationQueueEntry->setRequest($request);
        $stationQueueEntry->setMedia($request->getTrack());

        $stationQueueEntry->setDuration($request->getTrack()->getCalculatedLength());
        $stationQueueEntry->setTimestampCued($now->getTimestamp());
        $this->em->persist($stationQueueEntry);

        $request->setPlayedAt($now->getTimestamp());
        $this->em->persist($request);

        $this->em->flush();

        $event->setNextSong($stationQueueEntry);
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
        $artistSeparators = [
            ', ',
            ' feat ',
            ' feat. ',
            ' & ',
            ' vs. ',
        ];
        $dividerString = chr(7);

        $artists = [];
        $titles = [];
        $latestSongIdsPlayed = [];

        foreach ($playedTracks as $playedTrack) {
            $title = trim($playedTrack['title']);
            $titles[$title] = $title;

            $artistParts = explode(
                $dividerString,
                str_replace($artistSeparators, $dividerString, $playedTrack['artist'])
            );

            foreach ($artistParts as $artist) {
                $artist = trim($artist);
                if (!empty($artist)) {
                    $artists[$artist] = $artist;
                }
            }

            $songId = $playedTrack['song_id'];
            if (!isset($latestSongIdsPlayed[$songId])) {
                $latestSongIdsPlayed[$songId] = $playedTrack['timestamp_cued'] ?? $playedTrack['timestamp_start'];
            }
        }

        $eligibleTracksWithoutSameTitle = [];

        foreach ($eligibleTracks as $mediaId => $track) {
            // Avoid all direct title matches.
            $title = trim($track['title']);

            if (isset($titles[$title])) {
                continue;
            }

            // Attempt to avoid an artist match, if possible.
            $artist = trim($track['artist']);

            $artistMatchFound = false;
            if (!empty($artist)) {
                $artistParts = explode($dividerString, str_replace($artistSeparators, $dividerString, $artist));
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
                return $mediaId;
            }

            $eligibleTracksWithoutSameTitle[$mediaId] = $track;
        }

        $mediaIdsByTimePlayed = [];

        foreach ($eligibleTracksWithoutSameTitle as $mediaId => $track) {
            $songId = $track['song_id'];

            $mediaIdsByTimePlayed[$mediaId] = $latestSongIdsPlayed[$songId] ?? 0;
        }

        asort($mediaIdsByTimePlayed);

        return array_key_first($mediaIdsByTimePlayed);
    }

    protected function logRecentSongHistory(
        CarbonInterface $now,
        array $recentSongHistoryForOncePerXSongs,
        array $recentSongHistoryForDuplicatePrevention
    ): void {
        $logOncePerXSongsSongHistory = [];
        foreach ($recentSongHistoryForOncePerXSongs as $row) {
            $logOncePerXSongsSongHistory[] = [
                'song' => $row['text'],
                'cued_at' => (string)(CarbonImmutable::createFromTimestamp(
                    $row['timestamp_cued'] ?? $row['timestamp_start'],
                    $now->getTimezone()
                )),
                'duration' => $row['duration'],
                'sent_to_autodj' => $row['sent_to_autodj'],
            ];
        }

        $logDuplicatePreventionSongHistory = [];
        foreach ($recentSongHistoryForDuplicatePrevention as $row) {
            $logDuplicatePreventionSongHistory[] = [
                'song' => $row['text'],
                'cued_at' => (string)(CarbonImmutable::createFromTimestamp(
                    $row['timestamp_cued'] ?? $row['timestamp_start'],
                    $now->getTimezone()
                )),
                'duration' => $row['duration'],
                'sent_to_autodj' => $row['sent_to_autodj'],
            ];
        }

        $this->logger->debug(
            'AutoDJ recent song playback history',
            [
                'history_once_per_x_songs' => $logOncePerXSongsSongHistory,
                'history_duplicate_prevention' => $logDuplicatePreventionSongHistory,
            ]
        );
    }

    protected function logPlayablePlaylists(
        string $type,
        array $eligiblePlaylists,
        array $logPlaylists
    ): void {
        $this->logger->info(
            sprintf(
                '%d playable playlist(s) of type "%s" found.',
                count($eligiblePlaylists),
                $type
            ),
            ['playlists' => $logPlaylists]
        );
    }
}
