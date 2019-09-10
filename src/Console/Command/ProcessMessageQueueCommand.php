<?php
namespace App\Console\Command;

use App\MessageQueue;
use Azura\Console\Command\CommandAbstract;
use Psr\Log\LoggerInterface;
use const PHP_INT_MAX;

class ProcessMessageQueueCommand extends CommandAbstract
{
    public function __invoke(
        MessageQueue $messageQueue,
        LoggerInterface $logger,
        int $runtime = 0
    ) {
        if ($runtime < 1) {
            $runtime = PHP_INT_MAX;
            $logger->info('Running message queue processor with indefinite length.');
        } else {
            $logger->info(sprintf('Running message queue processor for %d seconds.', $runtime));
        }

        $messageQueue->consume([
            'max-runtime' => $runtime,
        ]);
    }
}
