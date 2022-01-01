<?php

declare(strict_types=1);

namespace App\Sync\NowPlaying\Task;

use App\Entity\Station;
use App\Radio\AutoDJ;

class BuildQueueTask implements NowPlayingTaskInterface
{
    public function __construct(
        protected AutoDJ\Queue $queue
    ) {
    }

    public function run(Station $station, bool $force = false): void
    {
        if ($station->useManualAutoDJ()) {
            return;
        }

        $this->queue->buildQueue($station, $force);
    }
}
