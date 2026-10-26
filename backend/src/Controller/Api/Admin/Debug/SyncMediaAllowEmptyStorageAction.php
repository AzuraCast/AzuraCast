<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\LogResult;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Sync\Task\CheckMediaTask;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/station/{station_id}/sync-media-allow-empty-storage',
        operationId: 'adminDebugSyncStationMediaAllowEmptyStorage',
        summary: 'Run the media sync for this station without skipping cleanup when its storage shows no files.',
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
final class SyncMediaAllowEmptyStorageAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;
    use LoggerAwareTrait;

    public function __construct(
        private readonly CheckMediaTask $checkMediaTask,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit($this->environment->getSyncLongExecutionTime());

        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        try {
            $this->checkMediaTask->importMusic(
                $request->getStation()->media_storage_location,
                allowEmptyStorage: true
            );
        } finally {
            $this->logger->popHandler();
        }

        return $response->withJson(
            LogResult::fromTestHandlerRecords($testHandler->getRecords())
        );
    }
}
