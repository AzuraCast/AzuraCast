<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Console\Command\Sync\SingleTaskCommand;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use DateTimeZone;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final class IndexAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly QueueManagerInterface $queueManager,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly CacheInterface $cache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        $queueTotals = [];
        foreach (QueueNames::cases() as $queue) {
            $queueTotals[] = [
                'name' => $queue->value,
                'count' => $this->queueManager->getQueueCount($queue),
                'url' => $router->named(
                    'admin:debug:clear-queue',
                    ['queue' => $queue->value]
                ),
            ];
        }

        $syncTasks = [];
        $now = CarbonImmutable::now(new DateTimeZone('UTC'));
        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        foreach ($syncTasksEvent->getTasks() as $task) {
            $cacheKey = SingleTaskCommand::getCacheKey($task);
            $pattern = $task::getSchedulePattern();

            $cronExpression = new CronExpression($pattern);

            $syncTasks[] = [
                'task' => $task,
                'pattern' => $pattern,
                'time' => $this->cache->get($cacheKey, 0),
                'nextRun' => $cronExpression->getNextRunDate($now)->getTimestamp(),
                'url' => $router->named(
                    'admin:debug:sync',
                    ['task' => rawurlencode($task)]
                ),
            ];
        }

        $stations = [];
        foreach ($this->stationRepo->fetchArray() as $station) {
            $stations[] = [
                'id' => $station['id'],
                'name' => $station['name'],
                'clearQueueUrl' => $router->named(
                    'admin:debug:clear-station-queue',
                    ['station_id' => $station['id']]
                ),
                'getNextSongUrl' => $router->named(
                    'admin:debug:nextsong',
                    ['station_id' => $station['id']]
                ),
                'getNowPlayingUrl' => $router->named(
                    'admin:debug:nowplaying',
                    ['station_id' => $station['id']]
                ),
            ];
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminDebug',
            id: 'admin-debug',
            title: __('System Debugger'),
            props: [
                'clearCacheUrl' => $router->named('admin:debug:clear-cache'),
                'clearQueuesUrl' => $router->named('admin:debug:clear-queue'),
                'syncTasks' => $syncTasks,
                'queueTotals' => $queueTotals,
                'stations' => $stations,
            ]
        );
    }
}
