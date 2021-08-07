<?php

declare(strict_types=1);

namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\MessageQueue\AbstractQueueManager;
use App\MessageQueue\QueueManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        QueueManagerInterface $queueManager,
        ?string $queue = null
    ): int {
        $allQueues = AbstractQueueManager::getAllQueues();

        if (!empty($queue)) {
            if (in_array($queue, $allQueues, true)) {
                $queueManager->clearQueue($queue);

                $io->success(sprintf('Message queue "%s" cleared.', $queue));
            } else {
                $io->error(sprintf('Message queue "%s" does not exist.', $queue));
                return 1;
            }
        } else {
            foreach ($allQueues as $queueName) {
                $queueManager->clearQueue($queueName);
            }

            $io->success('All message queues cleared.');
        }

        return 0;
    }
}
