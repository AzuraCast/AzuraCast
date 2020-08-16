<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\BuildQueue;
use App\EventDispatcher;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
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
     *
     * @return string
     */
    public function annotateNextSong(Entity\Station $station, $asAutoDj = false): string
    {
        $queueRow = $this->queueRepo->getNextInQueue($station);
        if (!($queueRow instanceof Entity\StationQueue)) {
            return '';
        }

        $playlist = $queueRow->getPlaylist();
        if ($playlist instanceof Entity\StationPlaylist) {
            $now = CarbonImmutable::now($station->getTimezoneObject());

            if (!$this->scheduler->isPlaylistScheduledToPlayNow($playlist, $now)) {
                $this->logger->warning('Queue item is no longer scheduled to play right now; removing.');

                $this->em->remove($queueRow);
                $this->em->flush();

                return $this->annotateNextSong($station, $asAutoDj);
            }
        }

        $event = new AnnotateNextSong($queueRow, $asAutoDj);
        $this->dispatcher->dispatch($event);

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

        // Determine the "now" time for the queue.
        $stationTz = $station->getTimezoneObject();
        
        $currentSong = $this->songHistoryRepo->getCurrent($station);
        if ($currentSong instanceof Entity\SongHistory) {
            $nowTimestamp = $currentSong->getTimestampStart() + ($currentSong->getDuration() ?? 1);
            $now = CarbonImmutable::createFromTimestamp($nowTimestamp, $stationTz);
        } else {
            $now = CarbonImmutable::now($stationTz);
        }

        // Adjust "now" time from current queue.
        $backendOptions = $station->getBackendConfig();
        $maxQueueLength = $backendOptions->getAutoDjQueueLength();

        $upcomingQueue = $this->queueRepo->getUpcomingQueue($station);
        $queueLength = count($upcomingQueue);

        foreach ($upcomingQueue as $queueRow) {
            $queueRow->setTimestampCued($now->getTimestamp());
            $this->em->persist($queueRow);

            $duration = $queueRow->getDuration() ?? 1;
            $now = $now->addSeconds($duration);
        }

        $this->em->flush();

        if ($queueLength >= $maxQueueLength) {
            $this->logger->debug('AutoDJ queue is already at current max length (' . $maxQueueLength . ').');
            $this->logger->popProcessor();
            return;
        }

        // Build the remainder of the queue.
        while ($queueLength < $maxQueueLength) {

            $this->logger->debug('Adding to station queue.', [
                'now' => (string)$now,
            ]);

            $event = new BuildQueue($station, $now);
            $this->dispatcher->dispatch($event);

            $queueRow = $event->getNextSong();
            if ($queueRow instanceof Entity\SongHistory) {
                $duration = $queueRow->getDuration() ?? 1;
                $now = $now->addSeconds($duration);
            }

            $queueLength++;
        }

        $this->logger->popProcessor();
    }
}
