<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Repository\StationRequestRepository;
use App\Entity\Song;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistChild;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Radio\PlaylistParser;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The internal steps of the AutoDJ Queue building process.
 */
final class QueueBuilder implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly DuplicatePrevention $duplicatePrevention,
        private readonly CacheInterface $cache,
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StationRequestRepository $requestRepo,
        private readonly StationQueueRepository $queueRepo
    ) {
    }

    /**
     * @inheritDoc
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
        $expectedPlayTime = $event->getExpectedPlayTime();

        $activePlaylistsByType = [];
        foreach ($station->playlists as $playlist) {
            /** @var StationPlaylist $playlist */
            if ($playlist->isPlayable($event->isInterrupting())) {
                $type = $playlist->type->value;

                $subType = ($playlist->schedule_items->count() > 0) ? 'scheduled' : 'unscheduled';
                $activePlaylistsByType[$type . '_' . $subType][$playlist->id] = $playlist;
            }
        }

        if (empty($activePlaylistsByType)) {
            $this->logger->warning('No valid playlists detected. Skipping AutoDJ calculations.');
            return;
        }

        $recentSongHistoryForDuplicatePrevention = $this->queueRepo->getRecentlyPlayedByTimeRange(
            $station,
            $expectedPlayTime,
            $station->backend_config->duplicate_prevention_time_range
        );

        $this->logger->debug(
            'AutoDJ recent song playback history',
            [
                'history_duplicate_prevention' => $recentSongHistoryForDuplicatePrevention,
            ]
        );

        $suppressedPlaylistIds = $this->getClockwheelSuppressedIds($activePlaylistsByType, $expectedPlayTime);

        $typesToPlay = [
            PlaylistTypes::OncePerHour->value,
            PlaylistTypes::OncePerXSongs->value,
            PlaylistTypes::OncePerXMinutes->value,
            PlaylistTypes::Clockwheel->value,
            PlaylistTypes::Standard->value,
        ];
        $typesToPlayByPriority = [];
        foreach ($typesToPlay as $type) {
            $typesToPlayByPriority[] = $type . '_scheduled';
            $typesToPlayByPriority[] = $type . '_unscheduled';
        }

        foreach ($typesToPlayByPriority as $currentPlaylistType) {
            if (empty($activePlaylistsByType[$currentPlaylistType])) {
                continue;
            }

            $eligiblePlaylists = [];
            $logPlaylists = [];
            foreach ($activePlaylistsByType[$currentPlaylistType] as $playlistId => $playlist) {
                /** @var StationPlaylist $playlist */
                if (!$this->scheduler->shouldPlaylistPlayNow($playlist, $expectedPlayTime)) {
                    continue;
                }

                if (isset($suppressedPlaylistIds[$playlistId])) {
                    $this->logger->debug(
                        sprintf(
                            'Playlist "%s" suppressed by active clockwheel.',
                            $playlist->name
                        ),
                        ['clockwheel' => $suppressedPlaylistIds[$playlistId]]
                    );
                    continue;
                }

                $eligiblePlaylists[$playlistId] = $playlist->weight;

                $logPlaylists[] = [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                    'weight' => $playlist->weight,
                ];
            }

            if (empty($eligiblePlaylists)) {
                continue;
            }

            $this->logger->info(
                sprintf(
                    '%d playable playlist(s) of type "%s" found.',
                    count($eligiblePlaylists),
                    $currentPlaylistType
                ),
                ['playlists' => $logPlaylists]
            );

            $eligiblePlaylists = $this->weightedShuffle($eligiblePlaylists);

            // Loop through the playlists and attempt to play them with no duplicates first,
            // then loop through them again while allowing duplicates.
            foreach ([false, true] as $allowDuplicates) {
                foreach ($eligiblePlaylists as $playlistId => $weight) {
                    $playlist = $activePlaylistsByType[$currentPlaylistType][$playlistId];

                    $nextSongs = (PlaylistTypes::Clockwheel === $playlist->type)
                        ? $this->playSongFromClockwheel(
                            $playlist,
                            $recentSongHistoryForDuplicatePrevention,
                            $expectedPlayTime,
                            $allowDuplicates
                        )
                        : $this->playSongFromPlaylist(
                            $playlist,
                            $recentSongHistoryForDuplicatePrevention,
                            $expectedPlayTime,
                            $allowDuplicates
                        );

                    if ($event->setNextSongs($nextSongs)) {
                        $this->logger->info(
                            'Playable track(s) found and registered.',
                            [
                                'next_song' => (string)$event,
                            ]
                        );
                        return;
                    }
                }
            }
        }

        if ($event->isInterrupting()) {
            $this->logger->info('No interrupting tracks to play.');
        } else {
            $this->logger->error('No playable tracks were found.');
        }
    }

    /**
     * Apply a weighted shuffle to the given array in the form:
     *  [ key1 => weight1, key2 => weight2 ]
     *
     * Based on: https://gist.github.com/savvot/e684551953a1716208fbda6c4bb2f344
     *
     * @param array $original
     * @return array
     */
    private function weightedShuffle(array $original): array
    {
        $new = $original;
        $max = 1.0 / mt_getrandmax();

        array_walk(
            $new,
            static function (&$value) use ($max): void {
                $value = (mt_rand() * $max) ** (1.0 / $value);
            }
        );

        arsort($new);

        array_walk(
            $new,
            static function (&$value, $key) use ($original): void {
                $value = $original[$key];
            }
        );

        return $new;
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param DateTimeImmutable $expectedPlayTime
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     * @return StationQueue|StationQueue[]|null
     */
    private function playSongFromPlaylist(
        StationPlaylist $playlist,
        array $recentSongHistory,
        DateTimeImmutable $expectedPlayTime,
        bool $allowDuplicates = false
    ): StationQueue|array|null {
        if (PlaylistSources::RemoteUrl === $playlist->source) {
            return $this->getSongFromRemotePlaylist($playlist, $expectedPlayTime);
        }

        if ($playlist->backendMerge()) {
            $this->spmRepo->resetQueue($playlist);

            $queueEntries = array_filter(
                array_map(
                    function (StationPlaylistQueue $validTrack) use ($playlist, $expectedPlayTime) {
                        return $this->makeQueueFromApi($validTrack, $playlist, $expectedPlayTime);
                    },
                    $this->spmRepo->getQueue($playlist)
                )
            );

            if (!empty($queueEntries)) {
                $playlist->played_at = $expectedPlayTime;
                $this->em->persist($playlist);
                return $queueEntries;
            }
        } else {
            $validTrack = match ($playlist->order) {
                PlaylistOrders::Random => $this->getRandomMediaIdFromPlaylist(
                    $playlist,
                    $recentSongHistory,
                    $allowDuplicates
                ),
                PlaylistOrders::Sequential => $this->getSequentialMediaIdFromPlaylist(
                    $playlist,
                    $recentSongHistory,
                    $allowDuplicates
                ),
                PlaylistOrders::Shuffle => $this->getShuffledMediaIdFromPlaylist(
                    $playlist,
                    $recentSongHistory,
                    $allowDuplicates
                )
            };

            if (null !== $validTrack) {
                $queueEntry = $this->makeQueueFromApi($validTrack, $playlist, $expectedPlayTime);

                if (null !== $queueEntry) {
                    $playlist->played_at = $expectedPlayTime;
                    $this->em->persist($playlist);
                    return $queueEntry;
                }
            }
        }

        $this->logger->warning(
            sprintf('Playlist "%s" did not return a playable track.', $playlist->name),
            [
                'playlist_id' => $playlist->id,
                'playlist_order' => $playlist->order->value,
                'allow_duplicates' => $allowDuplicates,
            ]
        );
        return null;
    }

    /**
     * @param StationPlaylist $clockwheelPlaylist
     * @param array $recentSongHistory
     * @param DateTimeImmutable $expectedPlayTime
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     * @param int $depth Current nesting depth (clockwheels can contain other clockwheels).
     * @return StationQueue|StationQueue[]|null
     */
    private function playSongFromClockwheel(
        StationPlaylist $clockwheelPlaylist,
        array $recentSongHistory,
        DateTimeImmutable $expectedPlayTime,
        bool $allowDuplicates = false,
        int $depth = 0
    ): StationQueue|array|null {
        $maxDepth = 5;
        if ($depth >= $maxDepth) {
            $this->logger->warning(
                sprintf(
                    'Clockwheel "%s" exceeded maximum nesting depth of %d.',
                    $clockwheelPlaylist->name,
                    $maxDepth
                ),
                ['playlist_id' => $clockwheelPlaylist->id, 'depth' => $depth]
            );
            return null;
        }

        $children = $clockwheelPlaylist->child_items->toArray();
        if (empty($children)) {
            return null;
        }

        usort($children, static fn(StationPlaylistChild $a, StationPlaylistChild $b) => $a->position <=> $b->position);

        $totalSteps = count($children);
        $currentStep = $clockwheelPlaylist->clockwheel_step;
        $songsPlayed = $clockwheelPlaylist->clockwheel_songs_played;

        $savedStep = $currentStep;
        $savedSongsPlayed = $songsPlayed;

        for ($attempt = 0; $attempt < $totalSteps; $attempt++) {
            $stepIndex = ($currentStep + $attempt) % $totalSteps;
            $child = $children[$stepIndex];

            if ($child->isRequestSlot()) {
                $request = $this->requestRepo->getNextPlayableRequest(
                    $clockwheelPlaylist->station,
                    $expectedPlayTime
                );

                if (null === $request) {
                    $this->logger->debug(
                        sprintf('Clockwheel step %d: request slot has no pending requests, skipping.', $stepIndex)
                    );
                    if ($attempt === 0) {
                        $this->advanceClockwheelStep($clockwheelPlaylist, $totalSteps);
                        $songsPlayed = 0;
                    }
                    continue;
                }

                $this->logger->info(
                    sprintf(
                        'Clockwheel "%s" step %d/%d: playing request (song %d/%d).',
                        $clockwheelPlaylist->name,
                        $stepIndex + 1,
                        $totalSteps,
                        $songsPlayed + 1,
                        $child->song_count
                    ),
                    ['request_id' => $request->id]
                );

                $result = StationQueue::fromRequest($request);
                $this->em->persist($result);

                $request->played_at = $expectedPlayTime;
                $this->em->persist($request);

                $songsPlayed++;

                if ($attempt > 0) {
                    $clockwheelPlaylist->clockwheel_step = $stepIndex;
                }

                if ($songsPlayed >= $child->song_count) {
                    $this->advanceClockwheelStep($clockwheelPlaylist, $totalSteps);
                } else {
                    $clockwheelPlaylist->clockwheel_songs_played = $songsPlayed;
                }

                $clockwheelPlaylist->played_at = $expectedPlayTime;
                $this->em->persist($clockwheelPlaylist);

                $this->annotateClockwheelResult($result, $clockwheelPlaylist, $stepIndex + 1);

                return $result;
            }

            $childPlaylist = $child->childPlaylist;

            if (!$childPlaylist->is_enabled) {
                $this->logger->debug(
                    sprintf('Clockwheel step %d: child "%s" disabled, skipping.', $stepIndex, $childPlaylist->name)
                );
                if ($attempt === 0) {
                    $this->advanceClockwheelStep($clockwheelPlaylist, $totalSteps);
                    $songsPlayed = 0;
                }
                continue;
            }

            // Override mode: if this step allows requests, try a request first.
            if ($child->allow_requests) {
                $request = $this->requestRepo->getNextPlayableRequest(
                    $clockwheelPlaylist->station,
                    $expectedPlayTime
                );

                if (null !== $request) {
                    $this->logger->info(
                        sprintf(
                            'Clockwheel "%s" step %d/%d: request overrides "%s" (song %d/%d).',
                            $clockwheelPlaylist->name,
                            $stepIndex + 1,
                            $totalSteps,
                            $childPlaylist->name,
                            $songsPlayed + 1,
                            $child->song_count
                        ),
                        ['request_id' => $request->id, 'playlist_id' => $childPlaylist->id]
                    );

                    $result = StationQueue::fromRequest($request);
                    $this->em->persist($result);

                    $request->played_at = $expectedPlayTime;
                    $this->em->persist($request);

                    $songsPlayed++;

                    if ($attempt > 0) {
                        $clockwheelPlaylist->clockwheel_step = $stepIndex;
                    }

                    if ($songsPlayed >= $child->song_count) {
                        $this->advanceClockwheelStep($clockwheelPlaylist, $totalSteps);
                    } else {
                        $clockwheelPlaylist->clockwheel_songs_played = $songsPlayed;
                    }

                    $clockwheelPlaylist->played_at = $expectedPlayTime;
                    $this->em->persist($clockwheelPlaylist);

                    $this->annotateClockwheelResult($result, $clockwheelPlaylist, $stepIndex + 1);

                    return $result;
                }
            }

            $this->logger->info(
                sprintf(
                    'Clockwheel "%s" step %d/%d: playing from "%s" (song %d/%d).',
                    $clockwheelPlaylist->name,
                    $stepIndex + 1,
                    $totalSteps,
                    $childPlaylist->name,
                    $songsPlayed + 1,
                    $child->song_count
                ),
                ['playlist_id' => $childPlaylist->id]
            );

            $result = (PlaylistTypes::Clockwheel === $childPlaylist->type)
                ? $this->playSongFromClockwheel(
                    $childPlaylist,
                    $recentSongHistory,
                    $expectedPlayTime,
                    $allowDuplicates,
                    $depth + 1
                )
                : $this->playSongFromPlaylist(
                    $childPlaylist,
                    $recentSongHistory,
                    $expectedPlayTime,
                    $allowDuplicates
                );

            if (null !== $result) {
                $songsPlayed++;

                if ($attempt > 0) {
                    $clockwheelPlaylist->clockwheel_step = $stepIndex;
                }

                if ($songsPlayed >= $child->song_count) {
                    $this->advanceClockwheelStep($clockwheelPlaylist, $totalSteps);
                } else {
                    $clockwheelPlaylist->clockwheel_songs_played = $songsPlayed;
                }

                $clockwheelPlaylist->played_at = $expectedPlayTime;
                $this->em->persist($clockwheelPlaylist);

                $this->annotateClockwheelResult($result, $clockwheelPlaylist, $stepIndex + 1);

                return $result;
            }

            $this->logger->debug(
                sprintf('Clockwheel step %d: "%s" returned no track.', $stepIndex, $childPlaylist->name)
            );

            if ($attempt === 0) {
                $this->advanceClockwheelStep($clockwheelPlaylist, $totalSteps);
                $songsPlayed = 0;
            }
        }

        $this->logger->warning(
            sprintf('Clockwheel "%s" exhausted all children without a playable track.', $clockwheelPlaylist->name),
            ['playlist_id' => $clockwheelPlaylist->id]
        );

        $clockwheelPlaylist->clockwheel_step = $savedStep;
        $clockwheelPlaylist->clockwheel_songs_played = $savedSongsPlayed;
        $this->em->persist($clockwheelPlaylist);

        return null;
    }

    private function advanceClockwheelStep(StationPlaylist $playlist, int $totalSteps): void
    {
        $playlist->clockwheel_step = ($playlist->clockwheel_step + 1) % $totalSteps;
        $playlist->clockwheel_songs_played = 0;
        $this->em->persist($playlist);
    }

    /**
     * @param StationQueue|StationQueue[]|null $result
     */
    private function annotateClockwheelResult(
        StationQueue|array|null $result,
        StationPlaylist $clockwheelPlaylist,
        int $step
    ): void {
        if (null === $result) {
            return;
        }

        $items = is_array($result) ? $result : [$result];
        foreach ($items as $queue) {
            $queue->clockwheel_playlist = $clockwheelPlaylist;
            $queue->clockwheel_step = $step;
        }
    }

    /**
     * @param array<string, array<int, StationPlaylist>> $activePlaylistsByType
     * @return array<int, string> Suppressed playlist ID => parent clockwheel name
     */
    private function getClockwheelSuppressedIds(
        array $activePlaylistsByType,
        DateTimeImmutable $expectedPlayTime
    ): array {
        $suppressedIds = [];

        $clockwheelKeys = [
            PlaylistTypes::Clockwheel->value . '_scheduled',
            PlaylistTypes::Clockwheel->value . '_unscheduled',
        ];

        $activeClockwheels = [];
        foreach ($clockwheelKeys as $key) {
            if (empty($activePlaylistsByType[$key])) {
                continue;
            }

            foreach ($activePlaylistsByType[$key] as $clockwheel) {
                /** @var StationPlaylist $clockwheel */
                if (!$this->scheduler->shouldPlaylistPlayNow($clockwheel, $expectedPlayTime)) {
                    continue;
                }
                $activeClockwheels[] = $clockwheel;
            }
        }

        if (empty($activeClockwheels)) {
            return [];
        }

        foreach ($activeClockwheels as $clockwheel) {
            foreach ($clockwheel->child_items as $child) {
                $suppressedIds[$child->child_playlist_id] = $clockwheel->name;
            }
        }

        // When a clockwheel is active, suppress all Standard playlists.
        $standardKeys = [
            PlaylistTypes::Standard->value . '_scheduled',
            PlaylistTypes::Standard->value . '_unscheduled',
        ];
        $clockwheelNames = implode(', ', array_map(fn($cw) => $cw->name, $activeClockwheels));

        foreach ($standardKeys as $key) {
            if (empty($activePlaylistsByType[$key])) {
                continue;
            }
            foreach ($activePlaylistsByType[$key] as $playlistId => $playlist) {
                $suppressedIds[$playlistId] = $clockwheelNames;
            }
        }

        return $suppressedIds;
    }

    private function makeQueueFromApi(
        StationPlaylistQueue $validTrack,
        StationPlaylist $playlist,
        DateTimeImmutable $expectedPlayTime,
    ): ?StationQueue {
        $mediaToPlay = $this->em->find(StationMedia::class, $validTrack->media_id);
        if (!$mediaToPlay instanceof StationMedia) {
            return null;
        }

        $spm = $this->em->find(StationPlaylistMedia::class, $validTrack->spm_id);
        if ($spm instanceof StationPlaylistMedia) {
            $spm->played($expectedPlayTime->getTimestamp());
            $this->em->persist($spm);
        }

        $stationQueueEntry = StationQueue::fromMedia($playlist->station, $mediaToPlay);
        $stationQueueEntry->playlist = $playlist;

        $this->em->persist($stationQueueEntry);

        return $stationQueueEntry;
    }

    private function getSongFromRemotePlaylist(
        StationPlaylist $playlist,
        DateTimeImmutable $expectedPlayTime
    ): ?StationQueue {
        $mediaToPlay = $this->getMediaFromRemoteUrl($playlist);

        if (is_array($mediaToPlay)) {
            [$mediaUri, $mediaDuration] = $mediaToPlay;

            $playlist->played_at = $expectedPlayTime;
            $this->em->persist($playlist);

            $stationQueueEntry = new StationQueue(
                $playlist->station,
                Song::createFromText('Remote Playlist URL')
            );

            $stationQueueEntry->playlist = $playlist;
            $stationQueueEntry->autodj_custom_uri = $mediaUri;
            $stationQueueEntry->duration = $mediaDuration;

            $this->em->persist($stationQueueEntry);

            return $stationQueueEntry;
        }

        return null;
    }

    /**
     * Returns either an array containing the URL of a remote stream and the duration,
     * an array with a media id and the duration or null if no media has been found.
     *
     * @return array{string|null, int}|null
     */
    private function getMediaFromRemoteUrl(StationPlaylist $playlist): ?array
    {
        $remoteType = $playlist->remote_type ?? PlaylistRemoteTypes::Stream;

        // Handle a raw stream URL of possibly indeterminate length.
        if (PlaylistRemoteTypes::Stream === $remoteType) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            $duration = $this->scheduler->getPlaylistScheduleDuration($playlist);
            return [$playlist->remote_url, $duration];
        }

        // Handle a remote playlist containing songs or streams.
        $queueCacheKey = 'playlist_queue.' . $playlist->id;

        $mediaQueue = $this->cache->get($queueCacheKey);
        if (empty($mediaQueue)) {
            $mediaQueue = [];

            $playlistRemoteUrl = $playlist->remote_url;
            if (null !== $playlistRemoteUrl) {
                $playlistRaw = file_get_contents($playlistRemoteUrl);
                if (false !== $playlistRaw) {
                    $mediaQueue = PlaylistParser::getSongs($playlistRaw);
                }
            }
        }

        $mediaId = null;
        if (!empty($mediaQueue)) {
            $mediaId = array_shift($mediaQueue);
        }

        // Save the modified cache, sans the now-missing entry.
        $this->cache->set($queueCacheKey, $mediaQueue, 6000);

        return ($mediaId)
            ? [$mediaId, 0]
            : null;
    }

    private function getRandomMediaIdFromPlaylist(
        StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates
    ): ?StationPlaylistQueue {
        $mediaQueue = $this->spmRepo->getQueue($playlist);

        if ($playlist->avoid_duplicates) {
            return $this->duplicatePrevention->preventDuplicates($mediaQueue, $recentSongHistory, $allowDuplicates);
        }

        return array_shift($mediaQueue);
    }

    private function getSequentialMediaIdFromPlaylist(
        StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates = false
    ): ?StationPlaylistQueue {
        $mediaQueue = $this->spmRepo->getQueue($playlist);
        if (empty($mediaQueue)) {
            $this->spmRepo->resetQueue($playlist);
            $mediaQueue = $this->spmRepo->getQueue($playlist);
        }

        // Apply duplicate prevention if enabled for this playlist
        if ($playlist->avoid_duplicates) {
            $queueItem = $this->duplicatePrevention->preventDuplicates(
                $mediaQueue,
                $recentSongHistory,
                $allowDuplicates
            );
            if (null !== $queueItem) {
                return $queueItem;
            }
        }

        // Fallback: return first item in queue if duplicate prevention is disabled or no match found
        return array_shift($mediaQueue);
    }

    private function getShuffledMediaIdFromPlaylist(
        StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates
    ): ?StationPlaylistQueue {
        $mediaQueue = $this->spmRepo->getQueue($playlist);
        if (empty($mediaQueue)) {
            $this->spmRepo->resetQueue($playlist);
            $mediaQueue = $this->spmRepo->getQueue($playlist);
        }

        if (!$playlist->avoid_duplicates) {
            return array_shift($mediaQueue);
        }

        $queueItem = $this->duplicatePrevention->preventDuplicates($mediaQueue, $recentSongHistory, $allowDuplicates);
        if (null !== $queueItem || $allowDuplicates) {
            return $queueItem;
        }

        // Reshuffle the queue.
        $this->logger->warning(
            'Duplicate prevention yielded no playable song; resetting song queue.'
        );

        $this->spmRepo->resetQueue($playlist);
        $mediaQueue = $this->spmRepo->getQueue($playlist);

        return $this->duplicatePrevention->preventDuplicates($mediaQueue, $recentSongHistory);
    }

    public function getNextSongFromRequests(BuildQueue $event): void
    {
        if ($event->isInterrupting()) {
            return;
        }

        $expectedPlayTime = $event->getExpectedPlayTime();
        $station = $event->getStation();

        foreach ($station->playlists as $playlist) {
            /** @var StationPlaylist $playlist */
            if (!$playlist->isPlayable($event->isInterrupting())) {
                continue;
            }
            if (!$this->scheduler->shouldPlaylistPlayNow($playlist, $expectedPlayTime)) {
                continue;
            }

            // An active clockwheel handles requests via its own steps.
            if (PlaylistTypes::Clockwheel === $playlist->type) {
                $this->logger->debug(sprintf(
                    'Clockwheel "%s" is active; global request queue deferred to clockwheel steps.',
                    $playlist->name
                ));
                return;
            }

            // A playlist with "prioritize over requests" (e.g. a jingle) is due now.
            if ($playlist->backendPrioritizeOverRequests()) {
                $this->logger->debug(sprintf(
                    'Playlist "%s" is prioritized and due now; skipping request queue.',
                    $playlist->name
                ));
                return;
            }
        }

        $request = $this->requestRepo->getNextPlayableRequest($station, $expectedPlayTime);
        if (null === $request) {
            return;
        }

        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->id));

        $stationQueueEntry = StationQueue::fromRequest($request);
        $this->em->persist($stationQueueEntry);

        $request->played_at = $expectedPlayTime;
        $this->em->persist($request);

        $event->setNextSongs($stationQueueEntry);
    }
}
