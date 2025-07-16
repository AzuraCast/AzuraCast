<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Cache\QueueLogCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Monolog\Handler\TestHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;

/**
 * Public methods related to the AutoDJ Queue process.
 */
final class Queue
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly StationQueueRepository $queueRepo,
        private readonly Scheduler $scheduler,
        private readonly QueueLogCache $queueLogCache
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
        $expectedCueTime = Time::nowUtc();

        // Get expected play time of each item.
        $currentSong = $station->current_song;
        if (null !== $currentSong) {
            $expectedPlayTime = $this->addDurationToTime(
                $station,
                $currentSong->timestamp_start,
                $currentSong->duration
            );

            if ($expectedPlayTime < $expectedCueTime) {
                $expectedPlayTime = $expectedCueTime;
            }
        } else {
            $expectedPlayTime = $expectedCueTime;
        }

        $maxQueueLength = max($station->backend_config->autodj_queue_length, 2);

        $upcomingQueue = $this->queueRepo->getUnplayedQueue($station);

        $lastSongId = null;
        $queueLength = 0;

        foreach ($upcomingQueue as $queueRow) {
            if ($queueRow->getSentToAutodj()) {
                $expectedCueTime = $this->addDurationToTime(
                    $station,
                    $queueRow->getTimestampCued(),
                    $queueRow->getDuration()
                );

                if (0 === $queueLength) {
                    $queueLength = 1;
                }
            } else {
                if (!$this->isQueueRowStillValid($queueRow, $expectedPlayTime)) {
                    $this->em->remove($queueRow);
                    continue;
                }

                $queueRow->setTimestampCued($expectedCueTime);
                $expectedCueTime = $this->addDurationToTime($station, $expectedCueTime, $queueRow->getDuration());

                // Only append to queue length for uncued songs.
                $queueLength++;
            }

            $queueRow->setTimestampPlayed($expectedPlayTime);
            $this->em->persist($queueRow);

            $expectedPlayTime = $this->addDurationToTime($station, $expectedPlayTime, $queueRow->getDuration());

            $lastSongId = $queueRow->song_id;
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
                $queueRow->setTimestampCued($expectedCueTime);
                $queueRow->setTimestampPlayed($expectedPlayTime);
                $queueRow->updateVisibility();
                $this->em->persist($queueRow);
                $this->em->flush();

                $this->queueLogCache->setLog($queueRow, $testHandler->getRecords());

                $lastSongId = $queueRow->song_id;

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
            $queueRow->setTimestampCued($expectedPlayTime);
            $queueRow->setTimestampPlayed($expectedPlayTime);
            $queueRow->updateVisibility();

            $this->em->persist($queueRow);
            $this->em->flush();

            $this->queueLogCache->setLog($queueRow, $testHandler->getRecords());

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
        DateTimeInterface $now,
        ?float $duration
    ): CarbonImmutable {
        $duration ??= 1;

        $startNext = $station->backend_config->getCrossfadeDuration();

        $now = CarbonImmutable::instance($now)->addSeconds($duration);
        return ($duration >= $startNext)
            ? $now->subMilliseconds((int)($startNext * 1000))
            : $now;
    }

    private function isQueueRowStillValid(
        StationQueue $queueRow,
        DateTimeImmutable $expectedPlayTime
    ): bool {
        $playlist = $queueRow->getPlaylist();
        if (null === $playlist) {
            return true;
        }

        return $playlist->is_enabled &&
            $this->scheduler->isPlaylistScheduledToPlayNow(
                $playlist,
                $expectedPlayTime,
                true
            );
    }
}
