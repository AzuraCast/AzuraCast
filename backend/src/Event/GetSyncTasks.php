<?php

declare(strict_types=1);

namespace App\Event;

use App\Sync\Task\AbstractTask;
use Generator;

/**
 * @phpstan-type TaskClass class-string<AbstractTask>
 */
final class GetSyncTasks
{
    /** @var TaskClass[] */
    private array $tasks = [];

    /** @return Generator<TaskClass> */
    public function getTasks(): Generator
    {
        yield from $this->tasks;
    }

    /**
     * @param TaskClass $className
     */
    public function addTask(string $className): void
    {
        $this->tasks[] = $className;
    }

    public function addTasks(array $classNames): void
    {
        foreach ($classNames as $className) {
            $this->addTask($className);
        }
    }
}
