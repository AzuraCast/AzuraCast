<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Repository\StationRequestRepository;
use App\Entity\Station;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\LiquidsoapQueues;
use Psr\EventDispatcher\EventDispatcherInterface;

final class CheckRequestsTask extends AbstractTask
{
    public function __construct(
        private readonly StationRequestRepository $requestRepo,
        private readonly Adapters $adapters,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
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

    private function submitRequest(Station $station, StationRequest $request): void
    {
        // Send request to the station to play the request.
        $backend = $this->adapters->getBackendAdapter($station);

        if (!($backend instanceof Liquidsoap)) {
            return;
        }

        // Check for an existing SongHistory record and skip if one exists.
        $sq = $this->em->getRepository(StationQueue::class)->findOneBy(
            [
                'station' => $station,
                'request' => $request,
            ]
        );

        if (!$sq instanceof StationQueue) {
            // Log the item in SongHistory.
            $sq = StationQueue::fromRequest($request);
            $sq->setSentToAutodj();
            $sq->setTimestampCued(time());

            $this->em->persist($sq);
            $this->em->flush();
        }

        // Generate full Liquidsoap annotations
        $event = AnnotateNextSong::fromStationQueue($sq, true);
        $this->dispatcher->dispatch($event);

        $track = $event->buildAnnotations();

        // Queue request with Liquidsoap.
        $queue = LiquidsoapQueues::Requests;

        if (!$backend->isQueueEmpty($station, $queue)) {
            $this->logger->error('Skipping submitting request to Liquidsoap; current queue is occupied.');
            return;
        }

        $this->logger->debug('Submitting request to AutoDJ.', ['track' => $track]);

        $response = $backend->enqueue($station, $queue, $track);
        $this->logger->debug('AutoDJ request response', ['response' => $response]);

        // Log the request as played.
        $request->setPlayedAt(time());

        $this->em->persist($request);
        $this->em->flush();
    }
}
