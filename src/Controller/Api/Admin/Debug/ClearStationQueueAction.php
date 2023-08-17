<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationQueueRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Queue;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface;

final class ClearStationQueueAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly StationQueueRepository $queueRepo,
        private readonly Queue $queue,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
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

        return $response->withJson([
            'logs' => $testHandler->getRecords(),
        ]);
    }
}
