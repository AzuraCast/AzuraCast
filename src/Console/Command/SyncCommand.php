<?php

declare(strict_types=1);

namespace App\Console\Command;

use App;
use App\Sync\Runner;

class SyncCommand extends CommandAbstract
{
    public function __invoke(
        Runner $sync,
        ?string $task = null,
        bool $force = false
    ): int {
        $task ??= App\Event\GetSyncTasks::SYNC_NOWPLAYING;

        $sync->runSyncTask($task, $force);
        return 0;
    }
}
