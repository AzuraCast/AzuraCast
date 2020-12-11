<?php

namespace App\Sync;

use App\Event\GetSyncTasks;
use Psr\Container\ContainerInterface;

class TaskLocator
{
    protected ContainerInterface $di;

    protected array $tasks;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;

        $this->tasks = [
            GetSyncTasks::SYNC_NOWPLAYING => [
                Task\BuildQueueTask::class,
                Task\NowPlayingTask::class,
                Task\ReactivateStreamerTask::class,
            ],
            GetSyncTasks::SYNC_SHORT => [
                Task\CheckRequests::class,
                Task\RunBackupTask::class,
                Task\CleanupRelaysTask::class,
            ],
            GetSyncTasks::SYNC_MEDIUM => [
                Task\CheckMediaTask::class,
                Task\CheckFolderPlaylistsTask::class,
                Task\CheckUpdatesTask::class,
            ],
            GetSyncTasks::SYNC_LONG => [
                Task\RunAnalyticsTask::class,
                Task\RunAutomatedAssignmentTask::class,
                Task\CleanupHistoryTask::class,
                Task\CleanupStorageTask::class,
                Task\RotateLogsTask::class,
                Task\UpdateGeoLiteTask::class,
            ],
        ];
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
