<?php

namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\MessageQueue\QueueManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        QueueManager $queueManager
    ): int {
        $connections = $queueManager->getConnections();
        foreach ($connections as $connection) {
            $connection->cleanup();
        }

        $io->success('Message queue cleared.');
        return 0;
    }
}
