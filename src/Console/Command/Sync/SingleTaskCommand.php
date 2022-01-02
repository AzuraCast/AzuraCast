<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Sync\Task\AbstractTask;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:sync:task',
    description: 'Task to run a specific scheduled task.',
)]
class SingleTaskCommand extends CommandAbstract
{
    protected array $processes = [];

    public function __construct(
        protected ContainerInterface $di,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('task', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $task = $input->getArgument('task');

        if (!$this->di->has($task)) {
            $io->error('Task not found.');
            return 1;
        }

        $taskClass = $this->di->get($task);

        if (!($taskClass instanceof AbstractTask)) {
            $io->error('Specified class is not a synchronized task.');
            return 1;
        }

        $taskClass->run();
        return 0;
    }
}
