<?php

namespace App\Event;

use App\Sync\Task\AbstractTask;

class GetSyncTasks
{
    public const SYNC_NOWPLAYING = 'nowplaying';
    public const SYNC_SHORT = 'short';
    public const SYNC_MEDIUM = 'medium';
    public const SYNC_LONG = 'long';

    protected string $type;

    protected array $tasks = [];

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return AbstractTask[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function addTask(AbstractTask $task, ?string $key = null): void
    {
        if (null === $key) {
            $taskClassParts = explode("\\", get_class($task));
            $key = array_pop($taskClassParts);
        }

        $this->tasks[$key] = $task;
    }

    public function removeTask(string $key): void
    {
        if (isset($this->tasks[$key])) {
            unset($this->tasks[$key]);
        }
    }
}
