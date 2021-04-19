<?php

namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\Doctrine\Messenger\ClearEntityManagerSubscriber;
use App\Environment;
use App\EventDispatcher;
use App\MessageQueue\LogWorkerExceptionSubscriber;
use App\MessageQueue\QueueManager;
use App\MessageQueue\ResetArrayCacheMiddleware;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Worker;

class ProcessCommand extends CommandAbstract
{
    public function __invoke(
        MessageBus $messageBus,
        EventDispatcher $eventDispatcher,
        QueueManager $queueManager,
        LoggerInterface $logger,
        Environment $environment,
        ?int $runtime = 0,
        ?string $workerName = null
    ): int {
        $logger->notice(
            'Starting new Message Queue worker process.',
            [
                'runtime' => $runtime,
                'workerName' => $workerName,
            ]
        );

        if (null !== $workerName) {
            $queueManager->setWorkerName($workerName);
        }

        $receivers = $queueManager->getTransports();

        $eventDispatcher->addServiceSubscriber(ClearEntityManagerSubscriber::class);
        $eventDispatcher->addServiceSubscriber(LogWorkerExceptionSubscriber::class);
        $eventDispatcher->addServiceSubscriber(ResetArrayCacheMiddleware::class);

        if ($runtime <= 0) {
            $runtime = $environment->isProduction()
                ? 300
                : 30;
        }

        $eventDispatcher->addSubscriber(new StopWorkerOnTimeLimitListener($runtime, $logger));

        try {
            $worker = new Worker($receivers, $messageBus, $eventDispatcher, $logger);
            $worker->run();
        } catch (\Throwable $e) {
            $logger->error(
                sprintf('Message queue error: %s', $e->getMessage()),
                [
                    'workerName' => $workerName,
                    'exception' => $e,
                ]
            );
            return 1;
        }


        return 0;
    }
}
