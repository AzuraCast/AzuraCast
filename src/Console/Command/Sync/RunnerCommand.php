<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Environment;
use App\Event\GetSyncTasks;
use App\LockFactory;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'azuracast:sync:run',
    description: 'Task to run the minute\'s synchronized tasks.'
)]
class RunnerCommand extends CommandAbstract
{
    protected array $processes = [];

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected LockFactory $lockFactory,
        protected Environment $environment,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        $now = CarbonImmutable::now(new \DateTimeZone('UTC'));

        foreach ($syncTasksEvent->getTasks() as $taskClass) {
            $schedulePattern = $taskClass::getSchedulePattern();
            $cronExpression = new CronExpression($schedulePattern);

            if ($cronExpression->isDue($now)) {
                $this->start($taskClass, $io);
            }
        }

        $this->manageStartedEvents($io);
        return 0;
    }

    protected function start(
        string $taskClass,
        SymfonyStyle $io
    ): void {
        $taskShortName = (new \ReflectionClass($taskClass))->getShortName();
        $lockName = 'sync_task_' . $taskShortName;

        $lock = $this->lockFactory->createAndAcquireLock($lockName, 60);
        if (false === $lock) {
            $this->logger->error(
                sprintf('Could not obtain lock for task "%s"; skipping it.', $taskShortName)
            );
            return;
        }

        $process = new Process([
            'php',
            $this->environment->getBaseDirectory() . '/bin/console',
            'azuracast:sync:task',
            $taskClass,
        ], $this->environment->getBaseDirectory());

        $process->setTimeout(600);
        $process->setIdleTimeout(600);

        $stdout = [];
        $stderr = [];

        $io->info('Starting task: ' . $taskShortName);

        $process->run(function ($type, $data) use ($process, $io, &$stdout, &$stderr): void {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, getenv());

        $this->processes[$taskShortName] = [
            'process' => $process,
            'lock'    => $lock,
        ];
    }

    protected function manageStartedEvents(SymfonyStyle $io): void
    {
        while ($this->processes) {
            foreach ($this->processes as $processName => $processGroup) {
                /** @var Lock $lock */
                $lock = $processGroup['lock'];

                /** @var Process $process */
                $process = $processGroup['process'];

                // 10% chance that refresh will be called
                $refreshLocks = (\random_int(1, 100) <= 10);
                if ($refreshLocks) {
                    $lock->refresh();
                }

                if ($process->isRunning()) {
                    continue;
                }

                if ($process->isSuccessful()) {
                    $io->success('Task completed: ' . $processName);
                } else {
                    $io->error('Task failed: ' . $processName);
                }

                $lock->release();
                unset($this->processes[$processName]);
            }
        }

        \usleep(250000);
    }
}
