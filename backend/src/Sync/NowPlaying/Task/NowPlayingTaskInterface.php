<?php

declare(strict_types=1);

namespace App\Sync\NowPlaying\Task;

use App\Entity\Station;

interface NowPlayingTaskInterface
{
    public function run(Station $station): void;
}
