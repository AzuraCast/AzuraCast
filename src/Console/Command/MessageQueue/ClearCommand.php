<?php

declare(strict_types=1);

namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:queue:clear',
    description: 'Clear the contents of the message queue.',
    aliases: ['queue:clear']
)]
final class ClearCommand extends CommandAbstract
{
    public function __construct(
        private readonly QueueManagerInterface $queueManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('queue', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $queueName = $input->getArgument('queue');

        if (!empty($queueName)) {
            $queue = QueueNames::tryFrom($queueName);

            if (null !== $queue) {
                $this->queueManager->clearQueue($queue);
                $io->success(sprintf('Message queue "%s" cleared.', $queue->value));
            } else {
                $io->error(sprintf('Message queue "%s" does not exist.', $queueName));
                return 1;
            }
        } else {
            $this->queueManager->clearAllQueues();
            $io->success('All message queues cleared.');
        }

        return 0;
    }
}
