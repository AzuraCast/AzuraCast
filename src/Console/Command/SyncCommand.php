<?php
namespace App\Console\Command;

use App;
use App\Service\UptimeWait;
use App\Sync\Runner;

class SyncCommand extends CommandAbstract
{
    public function __invoke(
        Runner $sync,
        UptimeWait $uptimeWait,
        string $task = 'nowplaying'
    ) {
        $uptimeWait->waitForAll();

        switch ($task) {
            case 'long':
                $sync->syncLong();
                break;

            case 'medium':
                $sync->syncMedium();
                break;

            case 'short':
                $sync->syncShort();
                break;

            case 'nowplaying':
            default:
                define('NOWPLAYING_SEGMENT', 1);
                $sync->syncNowplaying();
                break;
        }

        return 0;
    }
}
