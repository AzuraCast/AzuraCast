<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Container\SettingsAwareTrait;
use App\Event\GetSyncTasks;
use App\Lock\LockFactory;
use App\Service\HighAvailability;
use App\Sync\Task\AbstractTask;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Psr\EventDispatcher\EventDispatcherInterface;
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
        private readonly HighAvailability $highAvailability,
        LockFactory $lockFactory
    ) {
        parent::__construct($lockFactory);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logToExtraFile('app_sync.log');

        $io = new SymfonyStyle($input, $output);

        if (!$this->highAvailability->isActiveServer()) {
            $this->logger->error('This instance is not the current active instance.');
            sleep(30);
            return 0;
        }

        $settings = $this->readSettings();
        if ($settings->getSyncDisabled()) {
            $io->error('Automated synchronization is temporarily disabled.');
            return 1;
        }

        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        $now = CarbonImmutable::now(new DateTimeZone('UTC'));

        foreach ($syncTasksEvent->getTasks() as $taskClass) {
            if ($taskClass::isDue($now, $this->environment, $settings)) {
                $this->start($io, $taskClass);
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
     * @param SymfonyStyle $io
     * @param class-string<AbstractTask> $taskClass
     */
    private function start(
        SymfonyStyle $io,
        string $taskClass,
    ): void {
        $taskShortName = SingleTaskCommand::getClassShortName($taskClass);

        $isLongTask = $taskClass::isLongTask();
        $timeout = ($isLongTask)
            ? $this->environment->getSyncLongExecutionTime()
            : $this->environment->getSyncShortExecutionTime();

        $this->lockAndRunConsoleCommand(
            $io,
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
