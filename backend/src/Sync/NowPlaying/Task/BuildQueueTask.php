<?php

declare(strict_types=1);

namespace App\Sync\NowPlaying\Task;

use App\Entity\Station;
use App\Radio\AutoDJ;

final readonly class BuildQueueTask implements NowPlayingTaskInterface
{
    public function __construct(
        private AutoDJ\Queue $queue
    ) {
    }

    public function run(Station $station): void
    {
        $this->queue->buildQueue($station);
    }
}
