<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Cache\SyncStatusCache;
use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\SyncTask;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Sync\Task\AbstractTask;
use App\Utilities\Time;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;

#[
    OA\Get(
        path: '/admin/debug/sync-tasks',
        operationId: 'getAdminDebugSyncTasks',
        summary: 'List all sync tasks and details about their run times.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: SyncTask::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ListSyncTasksAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly SyncStatusCache $syncStatusCache
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();

        $syncTasks = [];

        $now = Time::nowUtc();
        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        $settings = $this->readSettings();

        /** @var class-string<AbstractTask> $task */
        foreach ($syncTasksEvent->getTasks() as $task) {
            $shortName = new ReflectionClass($task)->getShortName();

            $pattern = $task::getSchedulePattern();
            $nextRun = $task::getNextRun($now, $this->environment, $settings);

            $syncTasks[] = new SyncTask(
                $shortName,
                $pattern,
                $this->syncStatusCache->getTaskLastRun($task) ?? 0,
                $nextRun,
                $router->named(
                    'api:admin:debug:sync',
                    ['task' => $shortName]
                ),
            );
        }

        return $response->withJson($syncTasks);
    }
}
