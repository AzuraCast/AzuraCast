<?php

namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\BuildQueue;
use App\EventDispatcher;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class AutoDJ
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SongHistoryRepository $songHistoryRepo;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected EventDispatcher $dispatcher;

    protected Logger $logger;

    protected Scheduler $scheduler;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SongHistoryRepository $songHistoryRepo,
        Entity\Repository\StationQueueRepository $queueRepo,
        EventDispatcher $dispatcher,
        Logger $logger,
        Scheduler $scheduler
    ) {
        $this->em = $em;
        $this->songHistoryRepo = $songHistoryRepo;
        $this->queueRepo = $queueRepo;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     *
     * @param Entity\Station $station
     * @param bool $asAutoDj
     */
    public function annotateNextSong(Entity\Station $station, $asAutoDj = false): string
    {
        $queueRow = $this->queueRepo->getNextInQueue($station);
        if (!($queueRow instanceof Entity\StationQueue)) {
            return '';
        }

        $this->logger->pushProcessor(function ($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName(),
            ];
            return $record;
        });

        $duration = $queueRow->getDuration() ?? 0;
        $now = $this->getNowFromCurrentSong($station);

        $this->logger->debug('Adjusting now based on duration of most recently cued song.', [
            'song' => $queueRow->getText(),
            'cued' => (string)$now,
            'duration' => $duration,
        ]);

        $now = $this->getAdjustedNow($station, $now, $duration);

        $event = new AnnotateNextSong($queueRow, $asAutoDj);
        $this->dispatcher->dispatch($event);

        $this->buildQueueFromNow($station, $now, true);

        $this->logger->popProcessor();

        return $event->buildAnnotations();
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

        $now = $this->getNowFromCurrentSong($station);

        $this->buildQueueFromNow($station, $now, false);

        $this->logger->popProcessor();
    }

    protected function getAdjustedNow(Entity\Station $station, CarbonInterface $now, int $duration): CarbonInterface
    {
        $backendConfig = $station->getBackendConfig();
        $startNext = $backendConfig->getCrossfadeDuration();

        $now = $now->addSeconds($duration);
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

        $currentSongDuration = ($currentSong->getDuration() ?? 1);
        $adjustedNow = $this->getAdjustedNow($station, $started, $currentSongDuration);

        $this->logger->debug(
            'Got currently playing song. Using start time and duration for initial value of now.',
            [
                'song' => $currentSong->getText(),
                'started' => (string)$started,
                'duration' => $currentSongDuration,
            ]
        );

        // Return either the current timestamp (if it's later) or the scheduled end time.
        return max($now, $adjustedNow);
    }

    protected function buildQueueFromNow(
        Entity\Station $station,
        CarbonInterface $now,
        bool $resetTimestampCued
    ): CarbonInterface {
        // Adjust "now" time from current queue.
        $backendOptions = $station->getBackendConfig();
        $maxQueueLength = $backendOptions->getAutoDjQueueLength();
        $stationTz = $station->getTimezoneObject();

        $upcomingQueue = $this->queueRepo->getUpcomingQueue($station);
        $queueLength = count($upcomingQueue);

        /*
         * Calculate now from the end of the queue if the queue has items.
         * This assumes that the queue should always be full if a new row is added every time a row is removed.
         * If the queue is empty, then we fall back to the value of now passed in by the caller, which may bor may
         * not be accurate but is the best we have.
         */
        foreach ($upcomingQueue as $queueRow) {
            if ($resetTimestampCued === true) {
                $queueRow->setTimestampCued($now->getTimestamp());
            }

            $timestampCued = CarbonImmutable::createFromTimestamp($queueRow->getTimestampCued(), $stationTz);
            $duration = $queueRow->getDuration() ?? 1;
            $now = $this->getAdjustedNow($station, $timestampCued, $duration);
        }

        if ($queueLength >= $maxQueueLength) {
            $this->logger->debug('AutoDJ queue is already at current max length (' . $maxQueueLength . ').');
            return $now;
        }

        // Build the remainder of the queue.
        while ($queueLength < $maxQueueLength) {
            $now = $this->cueNextSong($station, $now);
            $queueLength++;
        }

        return $now;
    }

    protected function cueNextSong(Entity\Station $station, CarbonInterface $now): CarbonInterface
    {
        $this->logger->debug('Adding to station queue.', [
            'now' => (string)$now,
        ]);

        // Push another test handler specifically for this one queue task.
        $testHandler = new TestHandler(Logger::DEBUG, true);
        $this->logger->pushHandler($testHandler);

        $event = new BuildQueue($station, $now);
        $this->dispatcher->dispatch($event);

        $this->logger->popHandler();

        $queueRow = $event->getNextSong();
        if ($queueRow instanceof Entity\StationQueue) {
            $queueRow->setLog($testHandler->getRecords());
            $this->em->persist($queueRow);

            $duration = $queueRow->getDuration() ?? 1;
            $now = $this->getAdjustedNow($station, $now, $duration);
        }

        return $now;
    }
}
