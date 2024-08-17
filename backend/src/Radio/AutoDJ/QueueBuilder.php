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
        'scheduledBonus' => 4
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
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param BuildQueue $event
     */
    public function calculateNextSong(BuildQueue $event): void
    {
        $this->logger->info('AzuraCast AutoDJ is calculating the next song to play...');

        $station = $event->getStation();
        $expectedPlayTime = $event->getExpectedPlayTime();
        $recentSongHistoryForDuplicatePrevention = $this->queueRepo->getRecentlyPlayedByTimeRange(
            $station,
            $expectedPlayTime,
            $station->getBackendConfig()->getDuplicatePreventionTimeRange()
        );

        $this->logger->debug(
            'AutoDJ recent song playback history',
            [
                'history_duplicate_prevention' => $recentSongHistoryForDuplicatePrevention,
            ]
        );

        $eligiblePlaylists = $this->getEligiblePlaylists($station, $expectedPlayTime);
        if(empty($eligiblePlaylists))
        {
            $this->logger->error('No valid playlists detected. Skipping AutoDJ calculations.');
            return;

        }
        //The identified playlists can now be shuffled based on weight rules.
        $eligiblePlaylistsByWeight = [];
        foreach($eligiblePlaylists as $playlistId => $playlist)
        {
            $eligiblePlaylistsByWeight[$playlistId] = $playlist->getWeight();
        }
        $eligiblePlaylistsByWeight = $this->weightedShuffle($eligiblePlaylistsByWeight);

        // Loop through the playlists and attempt to play them with no duplicates first,
        // then loop through them again while allowing duplicates.
        foreach ([false, true] as $allowDuplicates) {
            foreach ($eligiblePlaylistsByWeight as $playlistId => $weight) {
                $playlist = $eligiblePlaylists[$playlistId];

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
    if ($event->isInterrupting()) {
        $this->logger->info('No interrupting tracks to play.');
    } else {
        $this->logger->error('No playable tracks were found.');
    }

    }

    private function stationUsesPriorities(Station $station): bool
    {
        foreach($station->getPlaylists() as $playlist)
        {
            if(null != $playlist->getPriority())
            {
                return true;
            }
            return false;
        }

    }
    /**
     * For stations not using playlist priorities, generate a token priority for a playlist based on its type.
     * This preserves the somewhat arbitrary precedents that were previously defined:
     * Once per hour -> Once per X songs -> Once per X minutes -> Standard, scheduled -> unscheduled.
     */
    private function getLegacyPriority(StationPlaylist $playlist): int
    {
        $this->logger->debug(
            'Computing legacy, type-based priority for the following playlist.',
            [
                'name' => $playlist->getName(),
                'type' => $playlist->getType()->name
            ]
            );

        $priority = self::LEGACY_PRIORITIES[$playlist->getType()->value];
        $this->logger->debug(
            sprintf(
                'Base priority for this playlist type is %d.',
                $priority
            )
            );

        $schedules = $playlist->getScheduleItems();
        if($schedules->count() > 0)
        {
            $bonus = self::LEGACY_PRIORITIES['scheduledBonus'];
            $this->logger->debug(
                sprintf(
                    'Playlist has scheduled items. The priority is increased by %d.',
                    $bonus
                )
                );
$priority += $bonus;
        }        $this->logger->debug(
            sprintf(
                'Final computed priority for this playlist is %d.',
                $priority
            )
            );
            return $priority;
    }

    /**
     * Organizes station playlists by priority.
     * If no playlist priorities are defined, uses getLegacyPriority method to retain backward compatibility.
     * If the station playlists have a mix of defined and undefined priorities, then those playlists with undefined priorities will be interpreted as having a value of 0.
     * This could result in a playlist being unreachable depending on other priorities, so it is recommended that either all or none of your playlists should define a priority.
     * @param Station $station
     * @return array
     */
    private function getPlaylistsByPriority(Station $station): array
    {
        $playlists = [];
        $highest = 0;
        $usesPriorities = $this->stationUsesPriorities($station);
        if($usesPriorities)
        {
            $this->logger->debug(
                    'Station has playlist priorities defined.');

        }
        else
        {
            $this->logger->debug('Station has no playlist priorities defined. Playlists are prioritized based on their type and whether or not they have schedule items.');

        }
        foreach($station->getPlaylists() as $playlist)
        {
            if(!$usesPriorities)
            {
                $priority = $this->getLegacyPriority($playlist);
            }
            else
            {
                $priority = $playlist->getPriority();
                if(null === $priority)
                {
                    $this->logger->info(
                        'This playlist has no priority. It will be considered to have a priority of 0.',
                        [
                            'name' => $playlist->getName()
                        ]
                        );
                    $priority = 0;
                }
            }
            if($priority > $highest)
            {
                $highest = $priority;
            }
            $playlists[$priority][] = $playlist;
        }
        $playlists['highest'] = $highest;
        return $playlists;
    }

    /**
     * Convert the array returned by getPlaylistsByPriority into a summary for logging purposes.
     * @param array $playlists
     * @return array
     */
    private function convertPrioritizedPlaylistsToLog(array $playlists): array
    {
        $summary = [];
        for($i = $playlists['highest']; $i >= 0; $i--)
        {
            if(!isset($playlists[$i]))
            {
                continue;
            }
            foreach($playlists[$i] as $playlist)
            {
                $summary[$i][] = $playlist->getName();
            }
        }
        return $summary;
    }

    /**
     * Returns currently available playlists, according to priority and eligibility rules.
     * @param Station $station
     * @return array|null
     */
    private function getEligiblePlaylists(Station $station, CarbonInterface $expectedPlayTime)
    {
        $allPlaylists = $this->getPlaylistsByPriority($station);
        $prioritySummary = $this->convertPrioritizedPlaylistsToLog($allPlaylists);
        $this->logger->debug(
            'Station playlists are prioritized as follows.',
            $prioritySummary
        );
        $eligiblePlaylists = [];
        $logPlaylists = [];
        for($i = $allPlaylists['highest']; $i >= 0; $i--)
    {
        foreach($allPlaylists[$i] as $playlist)
        {
            if (!$this->scheduler->shouldPlaylistPlayNow($playlist, $expectedPlayTime)) {
                continue;
            }

            $eligiblePlaylists[$playlist->getId()] = $playlist;
            $logPlaylists[] = [
                'id' => $playlist->getId(),
                'name' => $playlist->getName(),
                'weight' => $playlist->getWeight(),
            ];

        }
        if(!empty($eligiblePlaylists))
        {
            $this->logger->info(
                sprintf(
                    '%d playable playlist(s) found at priority level %s.',
                    count($eligiblePlaylists),
                    $i
                ),
                ['playlists' => $logPlaylists]
            );

            return $eligiblePlaylists;
        }
        $this->logger->info(
            sprintf(
                'No playable playlists found at priority level %d',
                $i
            )
            );

    }
    $this->logger->error('No playable playlists found.');
    return null;

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
                $queueEntry = $this->makeQueueFromApi($validTrack, $playlist, $expectedPlayTime);

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

        $this->em->persist($stationQueueEntry);

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

    public function getNextSongFromRequests(BuildQueue $event): void
    {
        // Don't use this to cue requests.
        if ($event->isInterrupting()) {
            return;
        }

        $expectedPlayTime = $event->getExpectedPlayTime();

        $request = $this->requestRepo->getNextPlayableRequest($event->getStation(), $expectedPlayTime);
        if (null === $request) {
            return;
        }

        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

        $stationQueueEntry = StationQueue::fromRequest($request);
        $this->em->persist($stationQueueEntry);

        $request->setPlayedAt($expectedPlayTime->getTimestamp());
        $this->em->persist($request);

        $event->setNextSongs($stationQueueEntry);
    }
}
