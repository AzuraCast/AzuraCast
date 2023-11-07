<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Cache\DatabaseCache;
use App\Console\Command\Sync\SingleTaskCommand;
use App\Controller\SingleActionInterface;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use DateTimeZone;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

final class ListSyncTasksAction implements SingleActionInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly DatabaseCache $cache
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();

        $syncTasks = [];

        $now = CarbonImmutable::now(new DateTimeZone('UTC'));
        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        foreach ($syncTasksEvent->getTasks() as $task) {
            $cacheKey = SingleTaskCommand::getCacheKey($task);
            $shortName = SingleTaskCommand::getClassShortName($task);

            $pattern = $task::getSchedulePattern();

            $cronExpression = new CronExpression($pattern);

            $syncTasks[] = [
                'task' => $shortName,
                'pattern' => $pattern,
                'time' => $this->cache->getItem($cacheKey)->get() ?? 0,
                'nextRun' => $cronExpression->getNextRunDate($now)->getTimestamp(),
                'url' => $router->named(
                    'api:admin:debug:sync',
                    ['task' => rawurlencode($task)]
                ),
            ];
        }

        return $response->withJson($syncTasks);
    }
}
