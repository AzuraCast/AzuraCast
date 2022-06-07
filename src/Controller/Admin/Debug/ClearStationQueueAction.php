<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Entity\Repository\StationQueueRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Queue;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

final class ClearStationQueueAction
{
    public function __construct(
        private readonly Logger $logger,
        private readonly StationQueueRepository $queueRepo,
        private readonly Queue $queue,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        $station = $request->getStation();

        try {
            $this->queueRepo->clearUnplayed($station);
            $this->logger->debug('Current queue cleared.');
            $this->queue->buildQueue($station);
        } finally {
            $this->logger->popHandler();
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $testHandler->getRecords(),
            ]
        );
    }
}
