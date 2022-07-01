<?php

declare(strict_types=1);

namespace App\Event;

use App\Sync\Task\AbstractTask;
use Generator;

final class GetSyncTasks
{
    private array $tasks = [];

    /**
     * @return Generator|class-string<AbstractTask>[]
     */
    public function getTasks(): Generator
    {
        yield from $this->tasks;
    }

    /**
     * @param class-string<AbstractTask> $className
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
