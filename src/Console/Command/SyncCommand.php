<?php

declare(strict_types=1);

namespace App\Console\Command;

use App;
use App\Sync\Runner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sync:run',
    description: 'Run one or more scheduled synchronization tasks.',
)]
class SyncCommand extends CommandAbstract
{
    public function __construct(
        protected Runner $sync,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('task', InputArgument::OPTIONAL)
            ->addOption('force', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = $input->getArgument('task') ?? App\Event\GetSyncTasks::SYNC_NOWPLAYING;
        $force = (bool)$input->getOption('force');

        $this->sync->runSyncTask($task, $force);
        return 0;
    }
}
