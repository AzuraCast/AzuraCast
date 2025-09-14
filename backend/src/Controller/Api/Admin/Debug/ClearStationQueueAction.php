<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\LogResult;
use App\Entity\Repository\StationQueueRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\AutoDJ\Queue;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/station/{station_id}/clearqueue',
        operationId: 'adminDebugClearStationQueue',
        summary: 'Clear the upcoming song queue and generate a new one.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: LogResult::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
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

        return $response->withJson(
            LogResult::fromTestHandlerRecords($testHandler->getRecords())
        );
    }
}
