<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Container\SettingsAwareTrait;
use App\Event\GetSyncTasks;
use App\Lock\LockFactory;
use App\Sync\Task\AbstractTask;
use App\Utilities\Time;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function usleep;

#[AsCommand(
    name: 'azuracast:sync:run',
    description: 'Task to run the minute\'s synchronized tasks.'
)]
final class RunnerCommand extends AbstractSyncRunnerCommand
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        LockFactory $lockFactory
    ) {
        parent::__construct($lockFactory);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logToExtraFile('app_sync.log');

        $settings = $this->readSettings();
        if ($settings->sync_disabled) {
            $io = new SymfonyStyle($input, $output);
            $io->error('Automated synchronization is temporarily disabled.');
            return 1;
        }

        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        $now = Time::nowUtc();

        foreach ($syncTasksEvent->getTasks() as $taskClass) {
            if ($taskClass::isDue($now, $this->environment, $settings)) {
                $this->start($output, $taskClass);
            }
        }

        $this->manageStartedEvents();

        $settings->updateSyncLastRun();
        $this->writeSettings($settings);

        return 0;
    }

    private function manageStartedEvents(): void
    {
        while ($this->processes) {
            $this->checkRunningProcesses();
        }

        usleep(250000);
    }

    /**
     * @param OutputInterface $output
     * @param class-string<AbstractTask> $taskClass
     */
    private function start(
        OutputInterface $output,
        string $taskClass,
    ): void {
        $taskShortName = new ReflectionClass($taskClass)->getShortName();

        $isLongTask = $taskClass::isLongTask();
        $timeout = ($isLongTask)
            ? $this->environment->getSyncLongExecutionTime()
            : $this->environment->getSyncShortExecutionTime();

        $this->lockAndRunConsoleCommand(
            $output,
            $taskShortName,
            'sync_task',
            [
                'azuracast:sync:task',
                $taskClass,
            ],
            $timeout
        );
    }
}
