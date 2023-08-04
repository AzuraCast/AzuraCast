<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Cache\DatabaseCache;
use App\Container\ContainerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Sync\Task\AbstractTask;
use InvalidArgumentException;
use Monolog\LogRecord;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        private readonly DatabaseCache $cache
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('task', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logToExtraFile('app_sync.log');

        $io = new SymfonyStyle($input, $output);
        $task = $input->getArgument('task');

        try {
            $this->runTask($task);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * @param class-string $task
     * @param bool $force
     */
    public function runTask(
        string $task,
        bool $force = false
    ): void {
        if (!$this->di->has($task)) {
            throw new InvalidArgumentException('Task not found.');
        }

        $taskClass = $this->di->get($task);
        if (!($taskClass instanceof AbstractTask)) {
            throw new InvalidArgumentException('Specified class is not a synchronized task.');
        }

        $taskShortName = self::getClassShortName($task);
        $cacheKey = self::getCacheKey($task);

        $startTime = microtime(true);
        $this->logger->pushProcessor(
            function (LogRecord $record) use ($taskShortName) {
                $record->extra['task'] = $taskShortName;
                return $record;
            }
        );

        $this->logger->info('Starting sync task.');

        $taskClass->run($force);

        $this->logger->info('Sync task completed.', [
            'time' => microtime(true) - $startTime,
        ]);
        $this->logger->popProcessor();

        $cacheItem = $this->cache->getItem($cacheKey)
            ->set(time())
            ->expiresAfter(86400);
        $this->cache->save($cacheItem);
    }

    /**
     * @param class-string $taskClass
     * @return string
     */
    public static function getCacheKey(string $taskClass): string
    {
        return 'sync_last_run.' . self::getClassShortName($taskClass);
    }

    /**
     * @param class-string $taskClass
     * @return string
     */
    public static function getClassShortName(string $taskClass): string
    {
        return (new ReflectionClass($taskClass))->getShortName();
    }
}
