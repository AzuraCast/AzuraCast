<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Entity\Repository\SettingsRepository;
use App\Environment;
use App\Event\GetSyncTasks;
use App\LockFactory;
use App\Sync\Task\AbstractTask;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use DateTimeZone;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function usleep;

#[AsCommand(
    name: 'azuracast:sync:run',
    description: 'Task to run the minute\'s synchronized tasks.'
)]
final class RunnerCommand extends AbstractSyncCommand
{
    public function __construct(
        LoggerInterface $logger,
        LockFactory $lockFactory,
        Environment $environment,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly SettingsRepository $settingsRepo,
    ) {
        parent::__construct($logger, $lockFactory, $environment);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $settings = $this->settingsRepo->readSettings();
        if ($settings->getSyncDisabled()) {
            $io->error('Automated synchronization is temporarily disabled.');
            return 1;
        }

        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        $now = CarbonImmutable::now(new DateTimeZone('UTC'));

        foreach ($syncTasksEvent->getTasks() as $taskClass) {
            $schedulePattern = $taskClass::getSchedulePattern();
            $cronExpression = new CronExpression($schedulePattern);

            if ($cronExpression->isDue($now)) {
                $this->start($io, $taskClass);
            }
        }

        $this->manageStartedEvents($io);

        $settings->updateSyncLastRun();
        $this->settingsRepo->writeSettings($settings);

        return 0;
    }

    private function manageStartedEvents(SymfonyStyle $io): void
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
