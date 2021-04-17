<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\EventDispatcher;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Psr\Log\LoggerInterface;

class CheckRequests extends AbstractTask
{
    protected Adapters $adapters;

    protected EventDispatcher $dispatcher;

    protected Entity\Repository\StationRequestRepository $requestRepo;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Repository\StationRequestRepository $requestRepo,
        Adapters $adapters,
        EventDispatcher $dispatcher
    ) {
        parent::__construct($em, $logger);

        $this->requestRepo = $requestRepo;
        $this->dispatcher = $dispatcher;
        $this->adapters = $adapters;
    }

    /**
     * Manually process any requests for stations that use "Manual AutoDJ" mode.
     *
     * @param bool $force
     */
    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            if (!$station->useManualAutoDJ()) {
                continue;
            }

            $request = $this->requestRepo->getNextPlayableRequest($station);
            if (null === $request) {
                continue;
            }

            $this->submitRequest($station, $request);
        }
    }

    protected function submitRequest(Entity\Station $station, Entity\StationRequest $request): bool
    {
        // Send request to the station to play the request.
        $backend = $this->adapters->getBackendAdapter($station);

        if (!($backend instanceof Liquidsoap)) {
            return false;
        }

        // Check for an existing SongHistory record and skip if one exists.
        $sq = $this->em->getRepository(Entity\StationQueue::class)->findOneBy(
            [
                'station' => $station,
                'request' => $request,
            ]
        );

        if (!$sq instanceof Entity\StationQueue) {
            // Log the item in SongHistory.
            $sq = Entity\StationQueue::fromRequest($request);
            $sq->setTimestampCued(time());
            $sq->sentToAutodj();

            $this->em->persist($sq);
            $this->em->flush();
        }

        // Generate full Liquidsoap annotations
        $event = new AnnotateNextSong($sq, true);
        $this->dispatcher->dispatch($event);

        $track = $event->buildAnnotations();

        // Queue request with Liquidsoap.
        if (!$backend->isQueueEmpty($station)) {
            $this->logger->error('Skipping submitting request to Liquidsoap; current queue is occupied.');
            return false;
        }

        $this->logger->debug('Submitting request to AutoDJ.', ['track' => $track]);

        $response = $backend->enqueue($station, $track);
        $this->logger->debug('AutoDJ request response', ['response' => $response]);

        // Log the request as played.
        $request->setPlayedAt(time());

        $this->em->persist($request);
        $this->em->flush();

        return true;
    }
}
