<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Console\Command\Sync\SingleTaskCommand;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Event\GetSyncTasks;
use App\Http\Response;
use App\Http\ServerRequest;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

final class SyncAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SingleTaskCommand $taskCommand,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        return $response->withJson([
            'logs' => $testHandler->getRecords(),
        ]);
    }
}
