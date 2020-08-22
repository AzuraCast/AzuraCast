<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\BuildQueue;
use App\EventDispatcher;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
class AutoDJ
{
    private const CROSSFADE_NORMAL = 'normal';
    
    private const CROSSFADE_DISABLED = 'none';
    
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
        $this->logger->pushProcessor(function ($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName()
            ];
            return $record;
        });
            
            $queueRow = $this->queueRepo->getNextInQueue($station);
        if (!($queueRow instanceof Entity\StationQueue)) {
            return '';
        }

        $playlist = $queueRow->getPlaylist();
        $stationTz = $station->getTimezoneObject();
        $now = CarbonImmutable::now($stationTz);
        if ($playlist instanceof Entity\StationPlaylist) {
            $duration = $queueRow->getDuration();
            $now = $this->getNowFromCurrentSong($station);
            $now = $this->getAdjustedNow($station, $now, $duration);
            
            if (!$this->scheduler->isPlaylistScheduledToPlayNow($playlist, $now)) {
                $this->logger->warning('Queue item is no longer scheduled to play right now; removing.');

                $this->em->remove($queueRow);
                $this->em->flush();

                return $this->annotateNextSong($station, $asAutoDj);
            }
        }        

        $event = new AnnotateNextSong($queueRow, $asAutoDj);
        $this->dispatcher->dispatch($event);

        $now = $this->buildQueueFromNow($station, $now);
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
            $now = $this->buildQueueFromNow($station, $now);
            
        $this->logger->popProcessor();
    }

    protected function getStartNext(Entity\Station $station): float
    {
        $backendOptions = $station->getBackendConfig();
        $startNext = 0;
        $crossfade = round($backendOptions['crossfade'] ?? 2, 1);
        $crossfade_type = $backendOptions['crossfade_type'] ?? self::CROSSFADE_NORMAL;
        if (self::CROSSFADE_DISABLED !== $crossfade_type && $crossfade > 0) {
            $startNext = round($crossfade * 1.5, 2);
        }
        
        return $startNext;
    }
    
    protected function getAdjustedNow(Entity\Station $station, CarbonInterface $now, int $duration): CarbonInterface
    {
        $startNext = $this->getStartNext($station);
        $now = $now->addSeconds($duration);
        if ($duration >= $startNext) {
            return $now->subMicroseconds($startNext * 1000000);
        } else {
            return $now;
        }
    }
    
    protected function getNowFromCurrentSong(Entity\Station $station): CarbonInterface
    {
        $stationTz = $station->getTimezoneObject();
        $currentSong = $this->songHistoryRepo->getCurrent($station);
        if ($currentSong instanceof Entity\SongHistory) {
            $nowTimestamp = $currentSong->getTimestampStart() + ($currentSong->getDuration() ?? 1);
            $now = CarbonImmutable::createFromTimestamp($nowTimestamp, $stationTz);
        } else {
            $now = CarbonImmutable::now($stationTz);
        }

        return $now;
    }
    
    protected function buildQueueFromNow(Entity\Station $station, CarbonInterface $now): CarbonInterface
    {
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
            return $now;
        }
        
        // Build the remainder of the queue.
        while ($queueLength < $maxQueueLength) {
            $now = $this->cueNextSong($station, $now);
            $queueLength ++;
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
            $now = $now->addSeconds($duration);
        }
        
            return $now;
    }
}
