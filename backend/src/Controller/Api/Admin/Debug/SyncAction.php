<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Console\Command\Sync\SingleTaskCommand;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\LogResult;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/sync/{task}',
        operationId: 'adminDebugRunSyncTask',
        summary: 'Manually run a scheduled synchronized task by name.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        parameters: [
            new OA\Parameter(
                name: 'task',
                description: 'Synchronized task (either class name or "all").',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
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
final class SyncAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SingleTaskCommand $taskCommand
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var class-string|string $task */
        $task = $params['task'];

        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        try {
            if ('all' === $task) {
                $this->taskCommand->runAllTasks(true);
            } else {
                $this->taskCommand->runTask($task, true);
            }
        } finally {
            $this->logger->popHandler();
        }

        return $response->withJson(
            LogResult::fromTestHandlerRecords($testHandler->getRecords())
        );
    }
}
