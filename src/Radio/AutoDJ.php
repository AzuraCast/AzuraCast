<?php

namespace App\Radio;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\BuildQueue;
use App\EventDispatcher;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class AutoDJ
{
    protected ReloadableEntityManagerInterface $em;

    protected Entity\Repository\SongHistoryRepository $songHistoryRepo;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected EventDispatcher $dispatcher;

    protected Logger $logger;

    protected Scheduler $scheduler;

    protected Environment $environment;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Entity\Repository\SongHistoryRepository $songHistoryRepo,
        Entity\Repository\StationQueueRepository $queueRepo,
        EventDispatcher $dispatcher,
        Logger $logger,
        Scheduler $scheduler,
        Environment $environment
    ) {
        $this->em = $em;
        $this->songHistoryRepo = $songHistoryRepo;
        $this->queueRepo = $queueRepo;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        $this->environment = $environment;
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     *
     * @param Entity\Station $station
     * @param bool $asAutoDj
     * @param int $iteration The iteration of the current attempt to
     */
    public function annotateNextSong(
        Entity\Station $station,
        bool $asAutoDj = false,
        int $iteration = 1
    ): string {
        if ($iteration > 3) {
            $this->logger->error(
                'Too many attempts to get next song; giving up.',
                [
                    'station' => [
                        'id' => $station->getId(),
                        'name' => $station->getName(),
                    ],
                ]
            );
            return '';
        }

        $queueRow = $this->queueRepo->getNextInQueue($station);

        // Try to rebuild the queue if it's empty.
        if (!($queueRow instanceof Entity\StationQueue)) {
            $this->logger->info(
                'Queue is empty; rebuilding before attempting to get next song.',
                [
                    'station' => [
                        'id' => $station->getId(),
                        'name' => $station->getName(),
                    ],
                ]
            );

            $this->buildQueue($station);
            return $this->annotateNextSong($station, $asAutoDj, $iteration + 1);
        }

        // Check that the song coming up isn't the same song as what's currently being played.
        $currentSong = $this->songHistoryRepo->getCurrent($station);
        if (
            ($currentSong instanceof Entity\SongHistory)
            && $queueRow->getSongId() === $currentSong->getSongId()
        ) {
            $this->em->remove($queueRow);
            $this->em->flush();

            $this->logger->info(
                'Queue would play the same song again; removing and attempting to get next song.',
                [
                    'station' => [
                        'id' => $station->getId(),
                        'name' => $station->getName(),
                    ],
                ]
            );

            return $this->annotateNextSong($station, $asAutoDj, $iteration + 1);
        }

        $event = new AnnotateNextSong($queueRow, $asAutoDj);
        $this->dispatcher->dispatch($event);
        return $event->buildAnnotations();
    }

    public function buildQueue(Entity\Station $station): void
    {
        $this->logger->pushProcessor(
            function ($record) use ($station) {
                $record['extra']['station'] = [
                    'id' => $station->getId(),
                    'name' => $station->getName(),
                ];
                return $record;
            }
        );

        // Adjust "now" time from current queue.
        $now = $this->getNowFromCurrentSong($station);

        $backendOptions = $station->getBackendConfig();
        $maxQueueLength = $backendOptions->getAutoDjQueueLength();
        if ($maxQueueLength < 1) {
            $maxQueueLength = 1;
        }

        $stationTz = $station->getTimezoneObject();

        $upcomingQueue = $this->queueRepo->getUpcomingQueue($station);

        $lastSongId = null;
        $queueLength = 0;

        foreach ($upcomingQueue as $queueRow) {
            // Prevent the exact same track from being played twice during this loop
            if (null !== $lastSongId && $lastSongId === $queueRow->getSongId()) {
                $this->em->remove($queueRow);
                continue;
            }

            $queueLength++;
            $lastSongId = $queueRow->getSongId();

            $queueRow->setTimestampCued($now->getTimestamp());
            $this->em->persist($queueRow);

            $timestampCued = CarbonImmutable::createFromTimestamp($queueRow->getTimestampCued(), $stationTz);
            $now = $this->getAdjustedNow($station, $timestampCued, $queueRow->getDuration() ?? 1);
        }

        $this->em->flush();

        // Build the remainder of the queue.
        while ($queueLength < $maxQueueLength) {
            $queueRow = $this->cueNextSong($station, $now);
            if ($queueRow instanceof Entity\StationQueue) {
                $this->em->persist($queueRow);

                // Prevent the exact same track from being played twice during this loop
                if (null !== $lastSongId && $lastSongId === $queueRow->getSongId()) {
                    $this->em->remove($queueRow);
                } else {
                    $lastSongId = $queueRow->getSongId();
                    $now = $this->getAdjustedNow($station, $now, $queueRow->getDuration() ?? 1);
                }
            } else {
                break;
            }

            $this->em->flush();
            $queueLength++;
        }

        $this->logger->popProcessor();
    }

    protected function getAdjustedNow(Entity\Station $station, CarbonInterface $now, ?int $duration): CarbonInterface
    {
        $backendConfig = $station->getBackendConfig();
        $startNext = $backendConfig->getCrossfadeDuration();

        $now = $now->addSeconds($duration ?? 1);
        return ($duration >= $startNext)
            ? $now->subMilliseconds((int)($startNext * 1000))
            : $now;
    }

    protected function getNowFromCurrentSong(Entity\Station $station): CarbonInterface
    {
        $stationTz = $station->getTimezoneObject();
        $now = CarbonImmutable::now($stationTz);

        $currentSong = $this->songHistoryRepo->getCurrent($station);
        if (!($currentSong instanceof Entity\SongHistory)) {
            return $now;
        }

        $startTimestamp = $currentSong->getTimestampStart();
        $started = CarbonImmutable::createFromTimestamp($startTimestamp, $stationTz);

        $adjustedNow = $this->getAdjustedNow($station, $started, $currentSong->getDuration());

        // Return either the current timestamp (if it's later) or the scheduled end time.
        return max($now, $adjustedNow);
    }

    protected function cueNextSong(Entity\Station $station, CarbonInterface $now): ?Entity\StationQueue
    {
        $this->logger->debug(
            'Adding to station queue.',
            [
                'now' => (string)$now,
            ]
        );

        // Push another test handler specifically for this one queue task.
        $testHandler = new TestHandler($this->environment->getLogLevel(), true);
        $this->logger->pushHandler($testHandler);

        $event = new BuildQueue($station, $now);
        $this->dispatcher->dispatch($event);

        $this->logger->popHandler();

        $queueRow = $event->getNextSong();
        if ($queueRow instanceof Entity\StationQueue) {
            $queueRow->setLog($testHandler->getRecords());
        }

        return $queueRow;
    }
}
