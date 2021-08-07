<?php

declare(strict_types=1);

namespace App\Sync;

use App\Event\GetSyncTasks;
use Psr\Container\ContainerInterface;

class TaskLocator
{
    protected array $tasks;

    public function __construct(
        protected ContainerInterface $di
    ) {
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
                Task\CleanupLoginTokensTask::class,
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

        foreach ($this->tasks[$type] as $taskClass) {
            $event->addTask($this->di->get($taskClass));
        }
    }
}
