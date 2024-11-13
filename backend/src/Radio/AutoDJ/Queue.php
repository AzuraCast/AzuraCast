<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Utilities\Types;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;

/**
 * Public methods related to the AutoDJ Queue process.
 */
final class Queue
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly StationQueueRepository $queueRepo,
        private readonly Scheduler $scheduler,
    ) {
    }

    public function buildQueue(Station $station): void
    {
        // Early-fail if the station is disabled.
        if (!$station->supportsAutoDjQueue()) {
            $this->logger->info('Cannot build queue: station does not support AutoDJ queue.');
            return;
        }

        // Adjust "expectedCueTime" time from current queue.
        $tzObject = $station->getTimezoneObject();
        $expectedCueTime = CarbonImmutable::now($tzObject);

        // Get expected play time of each item.
        $currentSong = $station->getCurrentSong();
        if (null !== $currentSong) {
            $expectedPlayTime = $this->addDurationToTime(
                $station,
                CarbonImmutable::createFromTimestamp($currentSong->getTimestampStart(), $tzObject),
                $currentSong->getDuration()
            );

            if ($expectedPlayTime < $expectedCueTime) {
                $expectedPlayTime = $expectedCueTime;
            }
        } else {
            $expectedPlayTime = $expectedCueTime;
        }

        $maxQueueLength = $station->getBackendConfig()->getAutoDjQueueLength();
        if ($maxQueueLength < 2) {
            $maxQueueLength = 2;
        }

        $upcomingQueue = $this->queueRepo->getUnplayedQueue($station);

        $lastSongId = null;
        $queueLength = 0;
        $removedPlaylistMedia = [];
        $removedRequests = [];
        $restOfQueueIsInvalid = false;

        foreach ($upcomingQueue as $queueRow) {
            if ($queueRow->getSentToAutodj()) {
                $expectedCueTime = $this->addDurationToTime(
                    $station,
                    CarbonImmutable::createFromTimestamp($queueRow->getTimestampCued(), $tzObject),
                    $queueRow->getDuration()
                );

                if (0 === $queueLength) {
                    $queueLength = 1;
                }
            } else {
                $playlist = $queueRow->getPlaylist();
                $spm = $queueRow->getPlaylistMedia();
                $request = $queueRow->getRequest();

                if (
                    $restOfQueueIsInvalid
                    || (
                        !$this->isExemptFromValidation($queueRow)
                        && !$this->isQueueRowStillValid($queueRow, $expectedPlayTime)
                    )
                ) {
                    $this->logger->debug(
                        'Queue item is invalid and will be removed',
                        array_filter([
                            'id' => $queueRow->getId(),
                            'playlist' => $playlist?->getName(),
                            'song' => $spm?->getMedia()->getTitle(),
                        ])
                    );

                    if (null !== $spm) {
                        $removedPlaylistMedia[] = $spm;
                    }

                    if (null !== $request) {
                        $removedRequests[] = $request;
                    }

                    $restOfQueueIsInvalid = true;

                    $this->em->remove($queueRow);
                    continue;
                }

                $queueRow->setTimestampCued($expectedCueTime->getTimestamp());
                $expectedCueTime = $this->addDurationToTime($station, $expectedCueTime, $queueRow->getDuration());

                // Only append to queue length for uncued songs.
                $queueLength++;
            }

            $queueRow->setTimestampPlayed($expectedPlayTime->getTimestamp());
            $this->em->persist($queueRow);

            $expectedPlayTime = $this->addDurationToTime($station, $expectedPlayTime, $queueRow->getDuration());

            $lastSongId = $queueRow->getSongId();
        }

        // Queue any removed playlist items to play again.
        foreach ($removedPlaylistMedia as $spm) {
            $this->logger->debug(
                'Playlist media must be requeued on its original playlist.',
                [
                    'media' => $spm->getMedia()->getTitle(),
                    'playlist' => $spm->getPlaylist()->getName(),

                ]
            );

            $spm->requeue();
            $this->em->persist($spm);
        }

        // Mark any removed requests as unplayed so they requeue.
        foreach ($removedRequests as $request) {
            $this->logger->debug(
                'Request must be marked unplayed.',
                [
                    'media' => $request->getTrack()->getTitle(),
                    'Request id' => $request->getId(),

                ]
            );

            $request->setPlayedAt(0);
            $this->em->persist($request);
        }

        $this->em->flush();

        // Build the remainder of the queue.
        while ($queueLength < $maxQueueLength) {
            $this->logger->debug(
                'Adding to station queue.',
                [
                    'now' => (string)$expectedPlayTime,
                ]
            );

            // Push another test handler specifically for this one queue task.
            $testHandler = new TestHandler(LogLevel::DEBUG, true);
            $this->logger->pushHandler($testHandler);

            $event = new BuildQueue(
                $station,
                $expectedCueTime,
                $expectedPlayTime,
                $lastSongId
            );

            try {
                $this->dispatcher->dispatch($event);
            } finally {
                $this->logger->popHandler();
            }

            $nextSongs = $event->getNextSongs();

            if (empty($nextSongs)) {
                $this->em->flush();
                break;
            }

            foreach ($nextSongs as $queueRow) {
                $queueRow->setTimestampCued($expectedCueTime->getTimestamp());
                $queueRow->setTimestampPlayed($expectedPlayTime->getTimestamp());
                if (null === $queueRow->getSchedule()) {
                    $queueRow->setTimestampScheduled($expectedPlayTime->getTimestamp());
                }
                $queueRow->updateVisibility();
                $this->em->persist($queueRow);
                $this->em->flush();

                $this->setQueueRowLog($queueRow, $testHandler->getRecords());

                $lastSongId = $queueRow->getSongId();

                $expectedCueTime = $this->addDurationToTime(
                    $station,
                    $expectedCueTime,
                    $queueRow->getDuration()
                );
                $expectedPlayTime = $this->addDurationToTime(
                    $station,
                    $expectedPlayTime,
                    $queueRow->getDuration()
                );

                $queueLength++;
            }
        }
    }

    /**
     * @param Station $station
     * @return StationQueue[]|null
     */
    public function getInterruptingQueue(Station $station): ?array
    {
        // Early-fail if the station is disabled.
        if (!$station->supportsAutoDjQueue()) {
            $this->logger->notice('Cannot build queue: station does not support AutoDJ queue.');
            return null;
        }

        $tzObject = $station->getTimezoneObject();
        $expectedPlayTime = CarbonImmutable::now($tzObject);

        $this->logger->debug(
            'Fetching interrupting queue.',
            [
                'now' => (string)$expectedPlayTime,
            ]
        );

        // Push another test handler specifically for this one queue task.
        $testHandler = new TestHandler(LogLevel::DEBUG, true);
        $this->logger->pushHandler($testHandler);

        $event = new BuildQueue(
            $station,
            $expectedPlayTime,
            $expectedPlayTime,
            null,
            true
        );

        try {
            $this->dispatcher->dispatch($event);
        } finally {
            $this->logger->popHandler();
        }

        $nextSongs = $event->getNextSongs();

        if (empty($nextSongs)) {
            $this->em->flush();
            return null;
        }

        foreach ($nextSongs as $queueRow) {
            $queueRow->setIsPlayed();
            $queueRow->setTimestampCued($expectedPlayTime->getTimestamp());
            $queueRow->setTimestampPlayed($expectedPlayTime->getTimestamp());
            $queueRow->updateVisibility();

            $this->em->persist($queueRow);
            $this->em->flush();

            $this->setQueueRowLog($queueRow, $testHandler->getRecords());

            $expectedPlayTime = $this->addDurationToTime(
                $station,
                $expectedPlayTime,
                $queueRow->getDuration()
            );
        }

        return $nextSongs;
    }

    private function addDurationToTime(
        Station $station,
        CarbonInterface $now,
        ?int $duration
    ): CarbonInterface {
        $duration ??= 1;

        $startNext = $station->getBackendConfig()->getCrossfadeDuration();

        $now = $now->addSeconds($duration);
        return ($duration >= $startNext)
            ? $now->subMilliseconds((int)($startNext * 1000))
            : $now;
    }

    private function isQueueRowStillValid(
        StationQueue $queueRow,
        CarbonInterface $expectedPlayTime
    ): bool {
        $playlist = $queueRow->getPlaylist();
        if (null === $playlist) {
            return true;
        }
        $schedule = $queueRow->getSchedule();
        $station = $queueRow->getStation();

        if (
            null !== $schedule
            && $queueRow->getTimestampPlayed() < $queueRow->getTimestampScheduled()
        ) {
            $first = $this->queueRepo->getStartOfScheduleRun(
                $station,
                $schedule,
                $queueRow->getTimestampScheduled()
            );

            // Item is exempt from being invalidated if it's not the first to come from this schedule run.
            if (
                null !== $first
                && $first->getId() !== $queueRow->getId()
            ) {
                return true;
            }
        }

        $ctx = new SchedulerContext($playlist, $expectedPlayTime, true);
        $ctx->belowId = $queueRow->getId();

        return $playlist->getIsEnabled()
            && $this->scheduler->shouldPlaylistPlayNow($ctx);
    }

    /**
     * A queue item is exempt from validation if:
     *  - The playlist it belongs to has the merge setting enabled;
     *  - The item is not the first track to play from that playlist.
     * @param StationQueue $queueRow
     * @return bool
     */
    private function isExemptFromValidation(StationQueue $queueRow): bool
    {
        $playlist = $queueRow->getPlaylist();
        if (null === $playlist) {
            return false;
        }

        if (!$playlist->backendMerge()) {
            return false;
        }

        $station = $queueRow->getStation();
        $playlist = $queueRow->getPlaylist();
        if (null === $playlist) {
            return false;
        }

        $previousQueue = $this->queueRepo->getPreviousItem($station, $queueRow);
        if (null === $previousQueue) {
            return false;
        }

        $previousPlaylist = $previousQueue->getPlaylist();
        if (null === $previousPlaylist) {
            return false;
        }

        return $playlist->getId() === $previousPlaylist->getId();
    }

    public function getQueueRowLog(StationQueue $queueRow): ?array
    {
        return Types::arrayOrNull(
            $this->cache->get($this->getQueueRowLogCacheKey($queueRow))
        );
    }

    public function setQueueRowLog(StationQueue $queueRow, ?array $log): void
    {
        if (null !== $log) {
            $log = array_map(
                fn(LogRecord $logRecord) => $logRecord->formatted,
                $log
            );
        }

        $this->cache->set(
            $this->getQueueRowLogCacheKey($queueRow),
            $log,
            StationQueue::QUEUE_LOG_TTL
        );
    }

    private function getQueueRowLogCacheKey(StationQueue $queueRow): string
    {
        return 'queue_log.' . $queueRow->getIdRequired();
    }
}
