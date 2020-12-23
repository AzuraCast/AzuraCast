<?php

namespace App\Console\Command;

use App;
use App\Sync\Runner;

class SyncCommand extends CommandAbstract
{
    public function __invoke(
        Runner $sync,
        string $task = App\Event\GetSyncTasks::SYNC_NOWPLAYING,
        bool $force = false
    ): int {
        $sync->runSyncTask($task, $force);
        return 0;
    }
}
