<?php

declare(strict_types=1);

namespace App\Event;

use App\Sync\Task\AbstractTask;
use Generator;

class GetSyncTasks
{
    public const SYNC_NOWPLAYING = 'nowplaying';
    public const SYNC_SHORT = 'short';
    public const SYNC_MEDIUM = 'medium';
    public const SYNC_LONG = 'long';

    protected array $tasks = [];

    public function __construct(
        protected string $type
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Generator|AbstractTask[]
     */
    public function getTasks(): Generator
    {
        yield from $this->tasks;
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
