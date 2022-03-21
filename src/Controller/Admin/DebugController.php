<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Console\Application;
use App\Console\Command\Sync\SingleTaskCommand;
use App\Controller\AbstractLogViewerController;
use App\Entity;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\AbstractQueueManager;
use App\MessageQueue\QueueManagerInterface;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Session\Flash;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Messenger\MessageBus;

class DebugController extends AbstractLogViewerController
{
    protected TestHandler $testHandler;

    public function __construct(
        protected Logger $logger,
        protected Application $console,
        protected MessageBus $messageBus
    ) {
        $this->testHandler = new TestHandler(Logger::DEBUG, false);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        QueueManagerInterface $queueManager,
        EventDispatcherInterface $dispatcher,
        CacheInterface $cache,
    ): ResponseInterface {
        $queues = AbstractQueueManager::getAllQueues();

        $queueTotals = [];
        foreach ($queues as $queue) {
            $queueTotals[$queue] = $queueManager->getQueueCount($queue);
        }

        $syncTimes = [];
        $now = CarbonImmutable::now(new \DateTimeZone('UTC'));
        $syncTasksEvent = new GetSyncTasks();
        $dispatcher->dispatch($syncTasksEvent);

        foreach ($syncTasksEvent->getTasks() as $task) {
            $cacheKey = SingleTaskCommand::getCacheKey($task);
            $pattern = $task::getSchedulePattern();

            $cronExpression = new CronExpression($pattern);

            $syncTimes[$task] = [
                'pattern' => $pattern,
                'time'    => $cache->get($cacheKey, 0),
                'nextRun' => $cronExpression->getNextRunDate($now)->getTimestamp(),
            ];
        }

        return $request->getView()->renderToResponse(
            $response,
            'admin/debug/index',
            [
                'queue_totals' => $queueTotals,
                'sync_times'   => $syncTimes,
                'stations'     => $stationRepo->fetchArray(),
            ]
        );
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param SingleTaskCommand $taskCommand
     * @param class-string|string $task
     * @return ResponseInterface
     */
    public function syncAction(
        ServerRequest $request,
        Response $response,
        SingleTaskCommand $taskCommand,
        EventDispatcherInterface $eventDispatcher,
        string $task
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        if ('all' === $task) {
            $syncTasksEvent = new GetSyncTasks();
            $eventDispatcher->dispatch($syncTasksEvent);
            foreach ($syncTasksEvent->getTasks() as $taskClass) {
                $taskCommand->runTask($taskClass, true);
            }
        } else {
            /** @var class-string $task */
            $taskCommand->runTask($task, true);
        }

        $this->logger->popHandler();

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar'     => null,
                'title'       => __('Debug Output'),
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
    }

    public function nowplayingAction(
        ServerRequest $request,
        Response $response,
        NowPlayingTask $nowPlayingTask
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();
        $nowPlayingTask->run($station);

        $this->logger->popHandler();

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
    }

    public function nextSongAction(
        ServerRequest $request,
        Response $response,
        AutoDJ\Annotations $annotations,
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $nextSongAnnotated = $annotations->annotateNextSong(
            $request->getStation(),
            false
        );

        $this->logger->info('Annotated next song: ' . $nextSongAnnotated);
        $this->logger->popHandler();

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
    }

    public function clearStationQueueAction(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\StationQueueRepository $queueRepo,
        AutoDJ\Queue $queue
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();

        $queueRepo->clearUnplayed($station);

        $this->logger->debug('Current queue cleared.');

        $queue->buildQueue($station);

        $this->logger->popHandler();

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar'     => null,
                'title'       => __('Debug Output'),
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
    }

    public function telnetAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if ($backend instanceof Liquidsoap) {
            $command = $request->getParam('command');

            $telnetResponse = $backend->command($station, $command);
            $this->logger->debug(
                'Telnet Command Response',
                [
                    'response' => $telnetResponse,
                ]
            );
        }

        $this->logger->popHandler();

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
    }

    public function clearCacheAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        [, $resultOutput] = $this->console->runCommandWithArgs(
            'cache:clear'
        );

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect((string)$request->getRouter()->fromHere('admin:debug:index'));
    }

    public function clearQueueAction(
        ServerRequest $request,
        Response $response,
        ?string $queue = null
    ): ResponseInterface {
        $args = [];
        if (!empty($queue)) {
            $args['queue'] = $queue;
        }

        [, $resultOutput] = $this->console->runCommandWithArgs('queue:clear', $args);

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect((string)$request->getRouter()->fromHere('admin:debug:index'));
    }
}
