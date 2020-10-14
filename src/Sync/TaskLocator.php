<?php

namespace App\Sync;

use App\Event\GetSyncTasks;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TaskLocator implements EventSubscriberInterface
{
    protected ContainerInterface $di;

    protected array $tasks;

    public function __construct(ContainerInterface $di, array $tasks)
    {
        $this->di = $di;
        $this->tasks = $tasks;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GetSyncTasks::class => [
                ['assignTasks', 0],
            ],
        ];
    }

    public function assignTasks(GetSyncTasks $event): void
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
