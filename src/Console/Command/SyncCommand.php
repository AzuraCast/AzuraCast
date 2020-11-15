<?php

namespace App\Console\Command;

use App;
use App\Sync\Runner;

class SyncCommand extends CommandAbstract
{
    public function __invoke(
        Runner $sync,
        string $task = App\Event\GetSyncTasks::SYNC_NOWPLAYING
    ): int {
        $sync->runSyncTask($task);
        return 0;
    }
}
