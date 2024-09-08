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
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Entity\StationSchedule;
use App\Event\Radio\BuildQueue;
use App\Radio\PlaylistParser;
use App\Utilities\DateRange;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The internal steps of the AutoDJ Queue building process.
 */
final class QueueBuilder implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    private const array LEGACY_PRIORITIES = [
        PlaylistTypes::OncePerHour->value => 6,
        PlaylistTypes::OncePerXSongs->value => 4,
        PlaylistTypes::OncePerXMinutes->value => 2,
        PlaylistTypes::Standard->value => 0,
    ];

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
                ['calculateNextSong', 0],
            ],
        ];
    }

    /**
     * Creates a QueueBuilderContext populated with all qualifying playlists.
     */
    private function createContext(Station $station, CarbonInterface $expectedPlayTime): QueueBuilderContext|null
    {
        $playlists = $station->getPlaylists();
        if ($playlists->isEmpty()) {
            return null;
        }

        // Find out when the last scheduled playlist played. Don't do schedule checks any earlier than this.
        $lastScheduledTrack = $this->queueRepo->getLatestScheduledTrack($station);
        if (null !== $lastScheduledTrack) {
            $lastScheduledTime = CarbonImmutable::createFromTimestamp(
                $lastScheduledTrack->getTimestampScheduled(),
                $station->getTimeZone()
            );
            if ($lastScheduledTime->greaterThan($expectedPlayTime)) {
                $this->logger->debug(
                    "schedule has drifted back in time. Using last scheduled track time instead of current playtime.",
                    [
                        'now' => $expectedPlayTime,
                        'lastScheduledTime' => $lastScheduledTime,
                    ]
                );
                $expectedPlayTime = $lastScheduledTime;
            }
        }
        $ctx = new QueueBuilderContext(
            $station,
            $this,
            $this->scheduler,
            $this->logger,
            $expectedPlayTime
        );
        foreach ($playlists as $playlist) {
            $ctx->registerPlaylist($playlist);
        }
        if (0 === $ctx->getPlaylistCount()) {
            $this->logger->warning('No eligible playlists found.');
            return null;
        }

        $this->logger->debug(
            sprintf(
                'The following $%d playlists are eligible.',
                $ctx->getPlaylistCount()
            ),
            $ctx->getLogPriorities()
        );
        return $ctx;
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     */
    public function calculateNextSong(BuildQueue $event): void
    {
        $this->logger->info('AzuraCast AutoDJ is calculating the next song to play...');

        $station = $event->getStation();
        $expectedPlayTime = $event->getExpectedPlayTime();
        $ctx = $this->createContext($station, $expectedPlayTime);
        if (null === $ctx) {
            return;
        }

        $recentSongHistory = $this->queueRepo->getRecentlyPlayedByTimeRange(
            $station,
            $expectedPlayTime,
            $station->getBackendConfig()->getDuplicatePreventionTimeRange()
        );

        $this->logger->debug(
            'AutoDJ recent song playback history',
            [
                'history_duplicate_prevention' => $recentSongHistory,
            ]
        );

        foreach ([false, true] as $allowDuplicates) {
            $gen = $this->getNextSongs($ctx, $recentSongHistory, $allowDuplicates);
            foreach ($gen as $songs) {
                if ($event->setNextSongs($songs)) {
                    $this->logger->info(
                        'Playable track(s) found and registered.',
                        [
                            'next_song' => (string) $event,
                        ]
                    );

                    return;
                }
            }
        }

        $this->logger->error("No playable tracks were found.");
    }

    /**
     * Gets a configured, legacy or default priority.
     */
    public function getPlaylistPriority(
        StationPlaylist $playlist
    ): int {
        $priority = $playlist->getPriority();
        if (null !== $priority) {
            return $priority;
        }

        /*
         * For stations not using playlist priorities, generate a token priority for a playlist based on its type.
         * This preserves the somewhat arbitrary precedents that were previously defined:
         * Once per hour -> Once per X songs -> Once per X minutes -> Standard, scheduled -> unscheduled.
         */
        $scheduled = count($playlist->getScheduleItems()) > 0 ? count(PlaylistTypes::cases()) : 0;
        return (self::LEGACY_PRIORITIES[$playlist->getType()->value] ?? 0) + $scheduled;
    }

    /**
     * Selects the next eligible playlist or song request.
     *
     * @return Generator<StationQueue|array|null>
     */
    private function getNextSongs(
        QueueBuilderContext $ctx,
        array $recentSongHistory,
        bool $allowDuplicates
    ): Generator {
        $requests = $this->requestRepo->getAllPotentialRequests($ctx->station);
        $this->logger->debug('Selecting next playlist.');

        foreach ($ctx->getPlaylists() as $playlistContext) {
            $playlist = $playlistContext->getPlaylistRequired();
            $logPlaylist = $this->getLogPlaylist($playlist);
            $this->logger->debug(
                'Playlist has been selected:',
                $logPlaylist
            );

            // Play requests (but don't play requests between merging playlist tracks)
            if (count($requests) > 0 && !$playlist->backendMerge()) {
                $request = null;

                // If this playlist is at or below general request priority, then general requests win.
                if (
                    $this->shouldConsiderGeneralRequests(
                        $ctx->station,
                        $this->getPlaylistPriority($playlist)
                    )
                ) {
                    $request = $this->getRequestFromGroup(
                        $requests,
                        $ctx->expectedPlayTime
                    );
                }

                if (null === $request) {
                    $request = $this->getRequestForPlaylist(
                        $playlist,
                        $requests,
                        $ctx->expectedPlayTime
                    );
                }

                if (null !== $request) {
                    $this->logger->info(
                        'Eligible request found',
                        [
                            'id' => $request->getId(),
                            'track' => $request->getTrack()->getTitle(),
                        ]
                    );

                    yield $this->playRequest(
                        $request,
                        $ctx->expectedPlayTime
                    );
                }
            }

            // Otherwise go forward with normal track selection.
            yield $this->playSongFromPlaylist($playlistContext, $recentSongHistory, $allowDuplicates);
        }

        return null;
    }

    private function getLogPlaylist(StationPlaylist $playlist): array
    {
        return [
            'id' => $playlist->getId(),
            'name' => $playlist->getName(),
            'type' => $playlist->getType()->name,
            'priority' => $playlist->getPriority(),
            'weight' => $playlist->getWeight(),
        ];
    }

    private function validateRequest(
        StationRequest $request,
        CarbonInterface $expectedPlayTime
    ): bool {
        return
            $request->shouldPlayNow($expectedPlayTime)
            && !$this->requestRepo->hasPlayedRecently(
                $request->getTrack(),
                $request->getStation()
            );
    }

    /**
     * Returns the first valid request from a group of requests, or null.
     */
    private function getRequestFromGroup(
        array &$requests,
        CarbonInterface $expectedPlayTime
    ): StationRequest|null {
        foreach ($requests as $request) {
            if ($this->validateRequest($request, $expectedPlayTime)) {
                return $request;
            }
        }

        return null;
    }

    /**
     * If available, gets a request for a specific playlist.
     */
    private function getRequestForPlaylist(
        StationPlaylist $playlist,
        array &$requests,
        CarbonInterface $expectedPlayTime
    ): StationRequest|null {
        $requestsByPlaylist = [];
        foreach ($requests as $request) {
            $track = $request->getTrack();
            $playlists = $track->getPlaylists();
            foreach ($playlists as $comparedPlaylist) {
                if ($playlist->getId() === $comparedPlaylist->getPlaylist()->getId()) {
                    $requestsByPlaylist[] = $request;
                }
            }
        }

        return $this->getRequestFromGroup(
            $requestsByPlaylist,
            $expectedPlayTime
        );
    }

    private function shouldConsiderGeneralRequests(
        Station $station,
        int $playlistPriority
    ): bool {
        $this->logger->debug('Checking if general requests should be considered at this time.');

        if ($station->requestsFollowFormat()) {
            $this->logger->debug(
                "Requests are required to follow the station's format. General requests are not permitted."
            );
            return false;
        }

        $requestPriority = $station->getRequestPriority();
        if (null === $requestPriority) {
            //Legacy mode where requests take precedence over everything.
            $this->logger->debug('No request priority defined, so requests should always be considered.');
            return true;
        }

        $this->logger->debug(
            sprintf(
                'Playlist priority: %d, request priority: %d. General requests should %sbe considered.',
                $playlistPriority,
                $requestPriority,
                $requestPriority >= $playlistPriority ? '' : 'not '
            )
        );

        return $requestPriority >= $playlistPriority;
    }

    private function playRequest(
        StationRequest $request,
        CarbonInterface $expectedPlayTime
    ): StationQueue {
        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

        $stationQueueEntry = StationQueue::fromRequest($request);
        $request->setPlayedAt($expectedPlayTime->getTimestamp());
        $this->em->persist($request);
        $stationQueueEntry->setTimestampScheduled($expectedPlayTime->getTimestamp());

        return $stationQueueEntry;
    }


    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param SchedulerContext $playlistContext
     * @param array $recentSongHistory
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     * @return StationQueue|StationQueue[]|null
     */
    private function playSongFromPlaylist(
        SchedulerContext $playlistContext,
        array $recentSongHistory,
        bool $allowDuplicates = false
    ): StationQueue|array|null {
        $playlist = $playlistContext->getPlaylistRequired();
        $expectedPlayTime = $playlistContext->getExpectedPlayTimeRequired();
        if (PlaylistSources::RemoteUrl === $playlist->getSource()) {
            return $this->getSongFromRemotePlaylist($playlist, $expectedPlayTime);
        }

        if ($playlist->backendMerge()) {
            $this->spmRepo->resetQueue($playlist);

            $queueEntries = array_filter(
                array_map(
                    function (
                        StationPlaylistQueue $validTrack
                    ) use ($playlistContext) {
                        return $this->makeQueueFromApi(
                            $validTrack,
                            $playlistContext
                        );
                    },
                    $this->spmRepo->getQueue($playlist)
                )
            );

            if (!empty($queueEntries)) {
                $playlist->setPlayedAt($expectedPlayTime->getTimestamp());
                $this->em->persist($playlist);
                return $queueEntries;
            }
        } else {
            $validTrack = match ($playlist->getOrder()) {
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
                $queueEntry = $this->makeQueueFromApi($validTrack, $playlistContext);

                if (null !== $queueEntry) {
                    $playlist->setPlayedAt($expectedPlayTime->getTimestamp());
                    $this->em->persist($playlist);
                    return $queueEntry;
                }
            }
        }

        $this->logger->warning(
            sprintf('Playlist "%s" did not return a playable track.', $playlist->getName()),
            [
                'playlist_id' => $playlist->getId(),
                'playlist_order' => $playlist->getOrder()->value,
                'allow_duplicates' => $allowDuplicates,
            ]
        );
        return null;
    }

    private function makeQueueFromApi(
        StationPlaylistQueue $validTrack,
        SchedulerContext $playlistContext
    ): ?StationQueue {
            $playlist = $playlistContext->getPlaylistRequired();
            $expectedPlayTime = $playlistContext->getExpectedPlayTimeRequired();
        $mediaToPlay = $this->em->find(StationMedia::class, $validTrack->media_id);
        if (!$mediaToPlay instanceof StationMedia) {
            return null;
        }

        $spm = $this->em->find(StationPlaylistMedia::class, $validTrack->spm_id);
        if ($spm instanceof StationPlaylistMedia) {
            $spm->played($expectedPlayTime->getTimestamp());
            $this->em->persist($spm);
        }

        $stationQueueEntry = StationQueue::fromMedia($playlist->getStation(), $mediaToPlay);
        $stationQueueEntry->setPlaylist($playlist);
        $stationQueueEntry->setPlaylistMedia($spm);
        $stationQueueEntry->setSchedule($playlistContext->schedule);
        if (null !== $playlistContext->dateRange) {
            $stationQueueEntry->setTimestampScheduled($playlistContext->dateRange->getStart()->getTimestamp());
        }

        return $stationQueueEntry;
    }

    private function getSongFromRemotePlaylist(
        StationPlaylist $playlist,
        CarbonInterface $expectedPlayTime
    ): ?StationQueue {
        $mediaToPlay = $this->getMediaFromRemoteUrl($playlist);

        if (is_array($mediaToPlay)) {
            [$mediaUri, $mediaDuration] = $mediaToPlay;

            $playlist->setPlayedAt($expectedPlayTime->getTimestamp());
            $this->em->persist($playlist);

            $stationQueueEntry = new StationQueue(
                $playlist->getStation(),
                Song::createFromText('Remote Playlist URL')
            );

            $stationQueueEntry->setPlaylist($playlist);
            $stationQueueEntry->setAutodjCustomUri($mediaUri);
            $stationQueueEntry->setDuration($mediaDuration);

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
        $remoteType = $playlist->getRemoteType() ?? PlaylistRemoteTypes::Stream;

        // Handle a raw stream URL of possibly indeterminate length.
        if (PlaylistRemoteTypes::Stream === $remoteType) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            $duration = $this->scheduler->getPlaylistScheduleDuration($playlist);
            return [$playlist->getRemoteUrl(), $duration];
        }

        // Handle a remote playlist containing songs or streams.
        $queueCacheKey = 'playlist_queue.' . $playlist->getId();

        $mediaQueue = $this->cache->get($queueCacheKey);
        if (empty($mediaQueue)) {
            $mediaQueue = [];

            $playlistRemoteUrl = $playlist->getRemoteUrl();
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

        if ($playlist->getAvoidDuplicates()) {
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

        if (!$playlist->getAvoidDuplicates()) {
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
}
