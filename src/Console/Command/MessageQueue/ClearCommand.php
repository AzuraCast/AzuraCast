<?php

namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\Entity\Repository\MessengerMessageRepository;
use App\MessageQueue\QueueManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        MessengerMessageRepository $messengerMessageRepo,
        ?string $queue = null
    ): int {
        $allQueues = QueueManager::getAllQueues();

        if (!empty($queue)) {
            if (in_array($queue, $allQueues, true)) {
                $messengerMessageRepo->clearQueue($queue);

                $io->success(sprintf('Message queue "%s" cleared.', $queue));
            } else {
                $io->error(sprintf('Message queue "%s" does not exist.', $queue));
                return 1;
            }
        } else {
            $messengerMessageRepo->clearQueue();
            $io->success('All message queues cleared.');
        }

        return 0;
    }
}
