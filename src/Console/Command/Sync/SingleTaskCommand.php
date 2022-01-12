<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Sync\Task\AbstractTask;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
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
        protected CacheInterface $cache,
        protected Logger $logger,
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

        try {
            $this->runTask($task);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * @param class-string $task
     */
    public function runTask(string $task): void
    {
        if (!$this->di->has($task)) {
            throw new \InvalidArgumentException('Task not found.');
        }

        $taskClass = $this->di->get($task);
        if (!($taskClass instanceof AbstractTask)) {
            throw new \InvalidArgumentException('Specified class is not a synchronized task.');
        }

        $taskShortName = self::getClassShortName($task);
        $cacheKey = self::getCacheKey($task);

        $startTime = microtime(true);
        $this->logger->pushProcessor(
            function ($record) use ($taskShortName) {
                $record['extra']['task'] = $taskShortName;
                return $record;
            }
        );

        $this->logger->info('Starting sync task.');

        $taskClass->run();

        $this->logger->info('Sync task completed.', [
            'time' => microtime(true) - $startTime,
        ]);
        $this->logger->popProcessor();

        $this->cache->set($cacheKey, time(), 86400);
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
        return (new \ReflectionClass($taskClass))->getShortName();
    }
}
