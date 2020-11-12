<?php

namespace App\Sync;

use App\Event\GetSyncTasks;
use Psr\Container\ContainerInterface;

class TaskLocator
{
    protected ContainerInterface $di;

    protected array $tasks;

    public function __construct(ContainerInterface $di, array $tasks)
    {
        $this->di = $di;
        $this->tasks = $tasks;
    }

    public function __invoke(GetSyncTasks $event): void
    {
        $type = $event->getType();
        if (!isset($this->tasks[$type])) {
            return;
        }

        $taskClasses = $this->tasks[$type];
        foreach ($taskClasses as $taskClass) {
            $event->addTask($this->di->get($taskClass));
        }
    }
}
