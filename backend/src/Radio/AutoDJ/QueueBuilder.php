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
use App\Event\Radio\BuildQueue;
use App\Radio\PlaylistParser;
use Carbon\CarbonInterface;
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
        PlaylistTypes::OncePerHour->value => 3,
        PlaylistTypes::OncePerXSongs->value => 2,
        PlaylistTypes::OncePerXMinutes->value => 1,
        PlaylistTypes::Standard->value => 0,
    ];
    private array $recentSongHistory;
    private Station $station;
    private CarbonInterface $expectedPlayTime;
    private array $playlists;
    private bool $legacyPriority = false;
    private array $requestsByPlaylist = [];
    private array $generalRequests = [];
    private array $eligibilityTable = [];

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
     * Called once to configure the object at the beginning of queue building.
     *
     * @param Station $station
     * @param CarbonInterface $expectedPlayTime
     * @return bool
     */
    private function setup(
        Station $station,
        CarbonInterface $expectedPlayTime
    ): bool {
        $this->station = $station;
        $this->expectedPlayTime = $expectedPlayTime;
        $this->recentSongHistory = $this->queueRepo->getRecentlyPlayedByTimeRange(
            $station,
            $expectedPlayTime,
            $station->getBackendConfig()->getDuplicatePreventionTimeRange()
        );
        $playlists = $station->getPlaylists();
        $priorities = [];
        if ($playlists->isEmpty()) {
            return false;
        }
        $this->legacyPriority = !$this->stationUsesPriorities();
        $this->logger->debug(
            sprintf(
                'Station %s playlist priorities.',
                $this->legacyPriority ? 'does not define' : 'defines'
            ),
        );
        $this->eligibilityTable = [];
        //Playlists with the merge option set must be handled specially because we don't control those.
        $backendMerge = [];
        foreach ($playlists as $playlist) {
            if (!$playlist->getIsEnabled()) {
                $this->logger->debug(
                    sprintf(
                        'Playlist "%s" is disabled.',
                        $playlist->getName()
                    )
                );
                continue;
            }
            if (0 === count($playlist->getMediaItems())) {
                $this->logger->debug(
                    sprintf(
                        'Playlist "%s" is empty.',
                        $playlist->getName()
                    )
                );
            }
            $playlistId = $playlist->getId();
            $this->eligibilityTable[$playlistId] = null;
            if ($playlist->backendMerge()) {
                $backendMerge[] = $playlist;
                continue;
            }
            $priority = $this->getPlaylistPriority($playlist, $this->legacyPriority);
            $priorities[$priority][$playlistId] = $playlist;
        }
        krsort($priorities);
        //Build our list of playlists, sorted first by priority, then by weight.
        $this->logger->debug(
            sprintf(
                'Station has %d playlists with the following priorities.',
                count($playlists)
            ),
            $this->getLogPriorities($priorities)
        );
                    $this->playlists = [];
        foreach ($priorities as $playlists) {
            $weights = [];
            foreach ($playlists as $playlist) {
                $weights[$playlist->getId()] = $playlist->getWeight();
            }
            $weights = $this->weightedShuffle($weights);
            foreach ($weights as $id => $weight) {
                $this->playlists[] = $playlists[$id];
            }
        }
        //Force backend merge playlists to the front.
        $this->playlists = array_merge($backendMerge, $this->playlists);
        //Create a mapping of requests to playlists in order to support limiting requests based on the current schedule.
        $requests = $this->requestRepo->getAllPotentialRequests($this->station, $this->expectedPlayTime);
        $this->generalRequests = $requests;
        $this->requestsByPlaylist = [];
        foreach ($requests as $request) {
            $track = $request->getTrack();
            foreach ($track->getPlaylists() as $playlistItem) {
                $playlist = $playlistItem->getPlaylist();
                $this->requestsByPlaylist[$playlist->getId()][] = $request;
            }
        }

        return true;
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param BuildQueue $event
     */

    public function calculateNextSong(BuildQueue $event): void
    {
        $this->logger->info('AzuraCast AutoDJ is calculating the next song to play...');
        $this->setup(
            $event->getStation(),
            $event->getExpectedPlayTime()
        );
        $this->logger->debug(
            'AutoDJ recent song playback history',
            [
                'history_duplicate_prevention' => $this->recentSongHistory,
            ]
        );
        foreach ([false, true] as $allowDuplicates) {
            while (null !== $songs = $this->getNextSongs($allowDuplicates)) {
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
            $this->reset();
        }
        $this->logger->error("No playable tracks were found.");
    }
/**
 * Checks if at least one of this station's playlists has a priority defined.
 * Priorities should be set on all or none of a station's playlists, not a mix of both.
 * If priorities are not used, then all playlists will be prioritized by type and whether they're scheduled.
 * This maintains legacy behaviour.
 * If priorities are in use, then any playlist lacking one will be interpreted as having a priority of 0.
 */
    private function stationUsesPriorities(): bool
    {
        $playlists = $this->station->getPlaylists();
        if ($playlists->isEmpty()) {
            return false;
        }
        foreach ($playlists as $playlist) {
            if (null !== $playlist->getPriority()) {
                return true;
            }
        }
        return false;
    }

    /**
     * For stations not using playlist priorities, generate a token priority for a playlist based on its type.
     * This preserves the somewhat arbitrary precedents that were previously defined:
     * Once per hour -> Once per X songs -> Once per X minutes -> Standard, scheduled -> unscheduled.
     */
    private function getLegacyPriority(StationPlaylist $playlist): int
    {
        $scheduled = count($playlist->getScheduleItems()) > 0 ? count(PlaylistTypes::cases()) : 0;
        return self::LEGACY_PRIORITIES[$playlist->getType()->value] + $scheduled;
    }

    /**
     * Gets a configured, legacy or default priority.
     * @param $playlist
     */
    private function getPlaylistPriority(StationPlaylist $playlist, bool $legacy): int
    {
        if ($legacy) {
            return $this->getLegacyPriority($playlist);
        }
        return $playlist->getPriority() ?? 0;
    }

    /**
     * Convert the array returned by getPlaylistsByPriority into a summary for logging purposes.
     * @param array $playlists
     * @return array
     */
    private function getLogPriorities(array $playlists): array
    {
        $summary = [];
        foreach ($playlists as $priority => $group) {
            foreach ($group as $playlist) {
                $summary[$priority][] = $playlist->getName();
            }
        }
        return $summary;
    }

    /**
     * Selects the next eligible playlist or song request, only checking eligibility when required.
     */
    private function getNextEligiblePlaylistOrRequest(): StationPlaylist|StationRequest|null
    {
        $this->logger->debug('Selecting next playlist.');
        while (null !== $key = key($this->playlists)) {
            $playlist = $this->playlists[$key];
            next($this->playlists);

            $logPlaylist = $this->getLogPlaylist($playlist);
            $this->logger->debug(
                'Playlist currently under consideration:',
                $logPlaylist,
            );
            $playlistId = $playlist->getId();
            if (null === $this->eligibilityTable[$playlistId]) {
                $this->logger->debug(
                    'Checking eligibility for playlist:',
                    $logPlaylist
                );
                $this->eligibilityTable[$playlistId] = $this->scheduler->shouldPlaylistPlayNow(
                    $playlist,
                    $this->expectedPlayTime
                );
            }
            if ($this->eligibilityTable[$playlistId]) {
                $this->logger->debug(
                    'Playlist has been selected:',
                    $logPlaylist
                );
                //If this playlist is at or below general request priority, then general requests win.
                if (
                    !$playlist->backendMerge()
                    && $this->shouldConsiderGeneralRequests(
                        $this->getPlaylistPriority($playlist, $this->legacyPriority)
                    )
                ) {
                    $request = $this->getRequestFromGroup($this->generalRequests);
                    if (null !== $request) {
                        $this->logger->info(
                            'Eligible request found',
                            [
                                'id' => $request->getId(),
                                'track' => $request->getTrack()
                                    ->getTitle(),
                            ]
                        );
                        //Rewind playlists so this playlist gets attempted again if this request doesn't go through.
                        prev($this->playlists);
                        return $request;
                    }
                }
                //Otherwise go forward with normal track selection.
                $this->logger->debug(
                    'No requests available. Proceeding with regular track selection',
                    $logPlaylist
                );
                return $playlist;
            }
        }
        $this->logger->warning('No eligible playlists found.');
        return null;
    }

    private function reset(): void
    {
        reset($this->playlists);
    }

    private function getNextSongs(bool $allowDuplicates): StationQueue|array|null
    {
        while (true) {
            $playlist = null;
            $request = null;
            $playlistOrRequest = $this->getNextEligiblePlaylistOrRequest();
            if (null === $playlistOrRequest) {
                //No songs available.
                return null;
            }
            if ($playlistOrRequest instanceof StationPlaylist) {
                $playlist = $playlistOrRequest;
            } else {
                /** @var StationPlaylist $playlist */
                $request = $playlistOrRequest;
            }
            //Try to play requests for this playlist first, then try to play it normally.
            if (
                null === $request
                && !$playlist->backendMerge()
            ) {
                $request = $this->getRequestForPlaylist($playlist);
            }
            if (null !== $request) {
                return $this->playRequest($request);
            }

            $song = $this->playSongFromPlaylist(
                $playlist,
                $this->recentSongHistory,
                $this->expectedPlayTime,
                $allowDuplicates
            );
            if (null !== $song) {
                return $song;
            }
        }
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

    //requests.
    private function validateRequest(StationRequest $request): bool
    {
        return $request->shouldPlayNow(
            $this->expectedPlayTime
        )
            && !$this->requestRepo->hasPlayedRecently(
                $request->getTrack(),
                $this->station
            );
    }

    /**
     * Returns the first valid request from a group of requests, or null.
     */
    private function getRequestFromGroup(array $requests): StationRequest|null
    {
        while (($request = current($requests)) instanceof StationRequest) {
            next($requests);
            if ($this->validateRequest($request)) {
                return $request;
            }
        }

        return null;
    }

    /**
     * If available, gets a request for a specific playlist.
     */
    private function getRequestForPlaylist(StationPlaylist $playlist): StationRequest|null
    {
        $playlistId = $playlist->getId();
        $requests = $this->requestsByPlaylist[$playlistId] ?? [];
        return $this->getRequestFromGroup($requests);
    }

    private function shouldConsiderGeneralRequests(int $playlistPriority = null): bool
    {
        $this->logger->debug('Checking if general requests should be considered at this time.');
        if (!$this->station->getRequestsFollowFormat()) {
            $this->logger->debug(
                "Requests are required to follow the station's format. General requests are not permitted."
            );
            return false;
        }
        $requestPriority = $this->station->getRequestPriority();
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

    private function playRequest(StationRequest $request): StationQueue
    {
        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

        $stationQueueEntry = StationQueue::fromRequest($request);
        $request->setPlayedAt($this->expectedPlayTime->getTimestamp());
        $this->em->persist($request);
        return $stationQueueEntry;
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
     * @param CarbonInterface $expectedPlayTime
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     * @return StationQueue|StationQueue[]|null
     */
    private function playSongFromPlaylist(
        StationPlaylist $playlist,
        array $recentSongHistory,
        CarbonInterface $expectedPlayTime,
        bool $allowDuplicates = false
    ): StationQueue|array|null {
        if (PlaylistSources::RemoteUrl === $playlist->getSource()) {
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
                //Prevent automatic queueing in case it's a duplicate.
                $queueEntry = $this->makeQueueFromApi($validTrack, $playlist, $expectedPlayTime, true);

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
        StationPlaylist $playlist,
        CarbonInterface $expectedPlayTime,
        bool $tentative = false
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

        $stationQueueEntry = StationQueue::fromMedia($playlist->getStation(), $mediaToPlay);
        $stationQueueEntry->setPlaylist($playlist);
        $stationQueueEntry->setPlaylistMedia($spm);
        if (!$tentative) {
            $this->em->persist($stationQueueEntry);
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

        return $this->duplicatePrevention->preventDuplicates($mediaQueue, $recentSongHistory, false);
    }
}
