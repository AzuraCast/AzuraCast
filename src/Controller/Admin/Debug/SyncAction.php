<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Console\Command\Sync\SingleTaskCommand;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

final class SyncAction
{
    public function __construct(
        private readonly Logger $logger,
        private readonly SingleTaskCommand $taskCommand,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param class-string|string $task
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $task
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        try {
            if ('all' === $task) {
                $syncTasksEvent = new GetSyncTasks();
                $this->eventDispatcher->dispatch($syncTasksEvent);
                foreach ($syncTasksEvent->getTasks() as $taskClass) {
                    $this->taskCommand->runTask($taskClass, true);
                }
            } else {
                /** @var class-string $task */
                $this->taskCommand->runTask($task, true);
            }
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
