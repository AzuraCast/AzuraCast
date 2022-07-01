<?php

declare(strict_types=1);

namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\Doctrine\Messenger\ClearEntityManagerSubscriber;
use App\Environment;
use App\MessageQueue\LogWorkerExceptionSubscriber;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\ResetArrayCacheMiddleware;
use Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Worker;
use Throwable;

#[AsCommand(
    name: 'azuracast:queue:process',
    description: 'Process the message queue.',
    aliases: ['queue:process']
)]
final class ProcessCommand extends CommandAbstract
{
    public function __construct(
        private readonly MessageBus $messageBus,
        private readonly CallableEventDispatcherInterface $eventDispatcher,
        private readonly QueueManagerInterface $queueManager,
        private readonly LoggerInterface $logger,
        private readonly Environment $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('runtime', InputArgument::OPTIONAL)
            ->addOption('worker-name', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runtime = (int)$input->getArgument('runtime');
        $workerName = $input->getOption('worker-name');

        $this->logger->notice(
            'Starting new Message Queue worker process.',
            [
                'runtime' => $runtime,
                'workerName' => $workerName,
            ]
        );

        if (null !== $workerName) {
            $this->queueManager->setWorkerName($workerName);
        }

        $receivers = $this->queueManager->getTransports();

        $this->eventDispatcher->addServiceSubscriber(ClearEntityManagerSubscriber::class);
        $this->eventDispatcher->addServiceSubscriber(LogWorkerExceptionSubscriber::class);
        $this->eventDispatcher->addServiceSubscriber(ResetArrayCacheMiddleware::class);

        if ($runtime <= 0) {
            $runtime = $this->environment->isProduction()
                ? 300
                : 30;
        }

        $busLogger = (LogLevel::DEBUG === $this->environment->getLogLevel())
            ? $this->logger
            : new NullLogger();

        $this->eventDispatcher->addSubscriber(new StopWorkerOnTimeLimitListener($runtime, $busLogger));

        try {
            $worker = new Worker($receivers, $this->messageBus, $this->eventDispatcher, $busLogger);
            $worker->run();
        } catch (Throwable $e) {
            $this->logger->error(
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
