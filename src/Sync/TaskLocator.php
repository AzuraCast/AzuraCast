<?php
namespace App\Sync;

use App\Sync\Task\AbstractTask;
use Psr\Container\ContainerInterface;

class TaskLocator
{
    public const SYNC_NOWPLAYING = 'nowplaying';

    public const SYNC_SHORT = 'short';

    public const SYNC_MEDIUM = 'medium';

    public const SYNC_LONG = 'long';

    protected ContainerInterface $di;

    protected array $tasks;

    public function __construct(ContainerInterface $di, array $tasks)
    {
        $this->di = $di;
        $this->tasks = $tasks;
    }

    /**
     * @param string $type
     *
     * @return AbstractTask[]
     */
    public function getTasks(string $type): array
    {
        if (!isset($this->tasks[$type])) {
            throw new \InvalidArgumentException('Invalid task type specified.');
        }

        $taskClasses = $this->tasks[$type];
        $tasks = [];
        foreach ($taskClasses as $taskClass) {
            $tasks[] = $this->di->get($taskClass);
        }

        return $tasks;
    }
}