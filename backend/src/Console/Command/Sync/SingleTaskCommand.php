<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Cache\SyncStatusCache;
use App\Container\ContainerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Event\GetSyncTasks;
use App\Sync\Task\AbstractTask;
use App\Utilities\Types;
use Doctrine\Inflector\InflectorFactory;
use InvalidArgumentException;
use Monolog\LogRecord;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:sync:task',
    description: 'Task to run a specific scheduled task.',
)]
final class SingleTaskCommand extends AbstractSyncCommand
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SyncStatusCache $syncStatusCache
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'task',
            InputArgument::REQUIRED,
            'Task name (i.e. check_updates)'
        )->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Force the task to run even if system checks would prevent it.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logToExtraFile('app_sync.log');

        $task = Types::string($input->getArgument('task'));
        $force = Types::bool($input->getOption('force'));

        try {
            if ('all' === $task) {
                $this->runAllTasks($force);
            } else {
                $this->runTask($task, $force);
            }
        } catch (InvalidArgumentException) {
            // Show all valid commands.
            $io = new SymfonyStyle($input, $output);

            $inflector = InflectorFactory::create()->build();

            $validTaskNames = [];
            foreach ($this->getValidTasks() as $taskClass) {
                $taskName = new ReflectionClass($taskClass)->getShortName();
                $taskName = str_replace('Task', '', $taskName);
                $validTaskNames[] = ' - ' . $inflector->tableize($taskName);
            }

            $io->error([
                'Invalid task. Valid tasks are:',
                implode("\n", $validTaskNames),
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * @param string|AbstractTask $task
     * @param bool $force
     */
    public function runTask(
        string|AbstractTask $task,
        bool $force = false
    ): void {
        if (is_string($task)) {
            $task = $this->getTask($task);
        }

        $taskShortName = new ReflectionClass($task::class)->getShortName();

        $startTime = microtime(true);
        $this->logger->pushProcessor(
            function (LogRecord $record) use ($taskShortName) {
                $record->extra['task'] = $taskShortName;
                return $record;
            }
        );

        $this->logger->info('Starting sync task.');

        try {
            $task->run($force);

            $this->logger->info('Sync task completed.', [
                'time' => microtime(true) - $startTime,
            ]);

            $this->syncStatusCache->markTaskAsRun($task::class);
        } finally {
            $this->logger->popProcessor();
        }
    }

    public function runAllTasks(
        bool $force = false
    ): void {
        foreach ($this->getValidTasks() as $taskClass) {
            $this->runTask($taskClass, $force);
        }
    }

    /**
     * @param class-string<AbstractTask>|string $taskName
     * @return AbstractTask
     */
    public function getTask(string $taskName): AbstractTask
    {
        $validTasks = $this->getValidTasks();

        /** @var class-string|null $taskToRun */
        $taskToRun = null;

        // Accept literal FQDN of class.
        if (in_array($taskName, $validTasks, true)) {
            $taskToRun = $taskName;
        } else {
            // Accept other forms like 'TaskName', 'task_name' or 'task-name'
            if (str_contains($taskName, '\\')) {
                $taskName = substr($taskName, strrpos($taskName, '\\') + 1);
            }

            $inflector = InflectorFactory::create()->build();
            $taskName = $inflector->classify($taskName);

            foreach ($validTasks as $validTaskName) {
                $validTaskShort = substr($validTaskName, strrpos($validTaskName, '\\') + 1);

                if ($validTaskShort === $taskName || $validTaskShort === $taskName . 'Task') {
                    $taskToRun = $validTaskName;
                    break;
                }
            }
        }

        if (null !== $taskToRun) {
            $taskClass = $this->di->get($taskToRun);
            if ($taskClass instanceof AbstractTask) {
                return $taskClass;
            }
        }

        throw new InvalidArgumentException('Task not found.');
    }

    /** @return class-string<AbstractTask>[] */
    private function getValidTasks(): array
    {
        $syncTasksEvent = new GetSyncTasks();
        $this->eventDispatcher->dispatch($syncTasksEvent);
        return $syncTasksEvent->getTasks();
    }
}
