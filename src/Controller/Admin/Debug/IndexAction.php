<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Console\Command\Sync\SingleTaskCommand;
use App\Entity\Repository\StationRepository;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\AbstractQueueManager;
use App\MessageQueue\QueueManagerInterface;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use DateTimeZone;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final class IndexAction
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
        Response $response
    ): ResponseInterface {
        $queues = AbstractQueueManager::getAllQueues();

        $queueTotals = [];
        foreach ($queues as $queue) {
            $queueTotals[$queue] = $this->queueManager->getQueueCount($queue);
        }

        $syncTimes = [];
        $now = CarbonImmutable::now(new DateTimeZone('UTC'));
        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        foreach ($syncTasksEvent->getTasks() as $task) {
            $cacheKey = SingleTaskCommand::getCacheKey($task);
            $pattern = $task::getSchedulePattern();

            $cronExpression = new CronExpression($pattern);

            $syncTimes[$task] = [
                'pattern' => $pattern,
                'time' => $this->cache->get($cacheKey, 0),
                'nextRun' => $cronExpression->getNextRunDate($now)->getTimestamp(),
            ];
        }

        return $request->getView()->renderToResponse(
            $response,
            'admin/debug/index',
            [
                'queue_totals' => $queueTotals,
                'sync_times' => $syncTimes,
                'stations' => $this->stationRepo->fetchArray(),
            ]
        );
    }
}
