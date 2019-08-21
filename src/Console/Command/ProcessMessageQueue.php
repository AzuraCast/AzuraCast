<?php
namespace App\Console\Command;

use App\MessageQueue;
use Azura\Console\Command\CommandAbstract;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessMessageQueue extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('queue:process')
            ->setDescription(__('Process the message queue.'))
            ->addArgument(
                'runtime',
                InputArgument::OPTIONAL,
                'The total length of time (in seconds) to spend processing requests before exiting.',
                0
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Logger $logger */
        $logger = $this->get(Logger::class);

        $runtime = (int)$input->getArgument('runtime');
        if ($runtime < 1) {
            $runtime = \PHP_INT_MAX;
            $logger->info('Running message queue processor with indefinite length.');
        } else {
            $logger->info(sprintf('Running message queue processor for %d seconds.', $runtime));
        }

        /** @var MessageQueue $message_queue */
        $message_queue = $this->get(MessageQueue::class);

        $message_queue->consume([
            'max-runtime' => $runtime,
        ]);
    }
}
