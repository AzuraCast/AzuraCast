<?php

declare(strict_types=1);

namespace App\Event;

use App\Sync\Task\AbstractTask;

final class GetSyncTasks
{
    /** @var class-string<AbstractTask>[] */
    private array $tasks = [];

    /** @return class-string<AbstractTask>[] */
    public function getTasks(): array
    {
        return $this->tasks;
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
