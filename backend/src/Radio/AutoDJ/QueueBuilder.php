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
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Radio\PlaylistParser;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
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

        $activePlaylistsByType = $this->assembleActivePlaylistsByType($event, $station->playlists);
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

        $playlistTypesToPlayByPriority = $this->assemblePlaylistTypesToPlayByPriority();

        foreach ($playlistTypesToPlayByPriority as $currentPlaylistType) {
            if (empty($activePlaylistsByType[$currentPlaylistType])) {
                continue;
            }

            [$eligiblePlaylists, $logPlaylists] = $this->assembleEligiblePlaylistsAndPlaylistsLog(
                $activePlaylistsByType,
                $currentPlaylistType,
                $expectedPlayTime
            );

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

                    if (
                        $event->setNextSongs(
                            $this->playSongFromPlaylist(
                                $playlist,
                                $recentSongHistoryForDuplicatePrevention,
                                $expectedPlayTime,
                                $allowDuplicates
                            )
                        )
                    ) {
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
     * @param Collection<int, StationPlaylist> $playlists
     *
     * @TODO: I'm unhappy about the "unspecific" typing of these PlaylistType::xyz->value . '_' . $subType keys...
     * @return array<string, array<int|string, StationPlaylist>>
     */
    private function assembleActivePlaylistsByType(
        BuildQueue $event,
        Collection $playlists,
        bool $skipGroupedPlaylists = true
    ): array {
        $activePlaylistsByType = [];

        foreach ($playlists as $playlist) {
            // @DEV: playlists that are part of a playlist group are not included in general playout rotation
            if ($skipGroupedPlaylists && $playlist->playlist_groups > 0) {
                continue;
            }

            if ($playlist->isPlayable($event->isInterrupting())) {
                $type = $playlist->type->value;

                $subType = ($playlist->schedule_items->count() > 0) ? 'scheduled' : 'unscheduled';
                $activePlaylistsByType[$type . '_' . $subType][$playlist->id] = $playlist;
            }
        }

        return $activePlaylistsByType;
    }

    /**
     * @TODO: I'm unhappy about the "unspecific" typing of these PlaylistType::xyz->value . '_<scheduled/unscheduled>' values...
     * @return string[]
     */
    private function assemblePlaylistTypesToPlayByPriority(): array
    {
        $typesToPlay = [
            PlaylistTypes::OncePerHour->value,
            PlaylistTypes::OncePerXSongs->value,
            PlaylistTypes::OncePerXMinutes->value,
            PlaylistTypes::Standard->value,
        ];

        $typesToPlayByPriority = [];
        foreach ($typesToPlay as $type) {
            $typesToPlayByPriority[] = $type . '_scheduled';
            $typesToPlayByPriority[] = $type . '_unscheduled';
        }

        return $typesToPlayByPriority;
    }

    /**
     * @param array<string, array<int|string, StationPlaylist>> $activePlaylistsByType
     *
     * @return array{
     *  eligiblePlaylists: array<int|string, int>,
     *  logPlaylists: array<array{
     *      id: int|string,
     *      name: string,
     *      weight: int
     *  }>
     * }
     */
    private function assembleEligiblePlaylistsAndPlaylistsLog(
        array $activePlaylistsByType,
        string $currentPlaylistType,
        DateTimeImmutable $expectedPlayTime
    ): array {
        $eligiblePlaylists = [];
        $logPlaylists = [];

        foreach ($activePlaylistsByType[$currentPlaylistType] as $playlistId => $playlist) {
            /** @var StationPlaylist $playlist */
            if (!$this->scheduler->shouldPlaylistPlayNow($playlist, $expectedPlayTime)) {
                continue;
            }

            $eligiblePlaylists[$playlistId] = $playlist->weight;

            $logPlaylists[] = [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'weight' => $playlist->weight,
            ];
        }

        return [
            'eligiblePlaylists' => $eligiblePlaylists,
            'logPlaylists' => $logPlaylists,
        ];
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

    private function playSongFromPlaylistGroup(
        StationPlaylist $playlistGroup,
        array $recentSongHistory,
        DateTimeImmutable $expectedPlayTime,
        bool $allowDuplicates = false
    ): StationQueue|array|null {
        // @TODO: iterate over entries, only one type of content shall be present, other playlists or media
        // - Need to handle the PlaylistOrders here to first decide what to do next
        $selectedPlaylist = match ($playlistGroup->order) {
            PlaylistOrders::Random => $this->getRandomPlaylistFromPlaylistGroup(),
            PlaylistOrders::Sequential => $this->getSequentialPlaylistFromPlaylistGroup(),
            PlaylistOrders::Shuffle => $this->getShuffledPlaylistFromPlaylistGroup()
        };

        if ($selectedPlaylist === null) {
            return null;
        }

        // @DEV: Playlists that contain playlists need to recursively resolve down until they reach a playlist that
        // is a media containing one in order to continue on with selecting something to play
        if ($selectedPlaylist->playlists->count() > 0) {
            $selectedPlaylist = $this->playSongFromPlaylistGroup(
                $selectedPlaylist,
                $recentSongHistory,
                $expectedPlayTime,
                $allowDuplicates
            );
        }

        // @TODO: what to do if the selected playlist has no entries???
        // - maybe i should prevent those from being returned by the "get" match methods above?
        // - still, what happens when that still returns nothing?

        // @TODO: Resolve
        $validTrack = null;

        // @TODO: I probably need to also advance the playlist groups queue
        if (null !== $validTrack) {
            $queueEntry = $this->makeQueueFromApi($validTrack, $selectedPlaylist, $expectedPlayTime);

            if (null !== $queueEntry) {
                $selectedPlaylist->played_at = $expectedPlayTime;
                $this->em->persist($selectedPlaylist);
                return $queueEntry;
            }
        }

        $this->logger->warning(
            sprintf('Playlist "%s" did not return a playable track.', $selectedPlaylist->name),
            [
                'playlist_id' => $selectedPlaylist->id,
                'playlist_order' => $selectedPlaylist->order->value,
                'allow_duplicates' => $allowDuplicates,
            ]
        );

        return null;
    }

    private function getRandomPlaylistFromPlaylistGroup(): ?StationPlaylist
    {
        // @TODO: implement
        return null;
    }

    private function getSequentialPlaylistFromPlaylistGroup(): ?StationPlaylist
    {
        // @TODO: implement
        return null;
    }

    private function getShuffledPlaylistFromPlaylistGroup(): ?StationPlaylist
    {
        // @TODO: implement
        return null;
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

        // @TODO: if playlist is part of playlist_groups
            // - they should get checked in the playSongFromPlaylist

        // @TODO: handle playlist groups
        if (PlaylistSources::Playlists === $playlist->source) {
            // @TODO: First need to define valid settings for playlist groups
            // - wouldn't allow advanced backend options at all
            //      - this would make handling groups much too hard imho as we would neet to figure out how to translate this into LS code
            // - wouldn't allow the following options in the beginning to keep the first version simple
            //      - avoid duplicate artists
            //      - include in on-demand player
            //      - hide metadata
            //      - allow requests
            // - wouldn't allow PlaylistTypes::Advanced, can't really represent these in LS Code
            // - other PlaylistType should all be fine
            // - all PlaylistOrders should be fine
            // - weight should be fine
            // - enabled / disabled should be fine

            // @TODO: Get playlists in playlist group
            // - handle assembleActivePlaylistsByType
            // - handle PlaylistType::xyz stuff
            // - handle shouldPlaylistPlayNow
            // - handle weighted shuffle
            // - handle duplicate check loop
            // Maybe I should extract this stuff from the calculateNextSong method
            // and put it into a method that can be re-used here... (done extraction)
            return $this->playSongFromPlaylistGroup(
                $playlist,
                $recentSongHistory,
                $expectedPlayTime,
                $allowDuplicates
            );
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
                PlaylistOrders::Sequential => $this->getSequentialMediaIdFromPlaylist($playlist),
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
        StationPlaylist $playlist
    ): ?StationPlaylistQueue {
        $mediaQueue = $this->spmRepo->getQueue($playlist);
        if (empty($mediaQueue)) {
            $this->spmRepo->resetQueue($playlist);
            $mediaQueue = $this->spmRepo->getQueue($playlist);
        }

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
        // Don't use this to cue requests.
        if ($event->isInterrupting()) {
            return;
        }

        $expectedPlayTime = $event->getExpectedPlayTime();
        $station = $event->getStation();

        // Check if any playlist marked with "Prioritize Over Requests" (e.g. a jingle) is due now.
        foreach ($station->playlists as $playlist) {
            /** @var StationPlaylist $playlist */
            if (
                $playlist->backendPrioritizeOverRequests() &&
                $playlist->isPlayable($event->isInterrupting()) &&
                $this->scheduler->shouldPlaylistPlayNow($playlist, $expectedPlayTime)
            ) {
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
