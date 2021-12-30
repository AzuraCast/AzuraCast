<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueManagerInterface;

class BuildStationQueue extends AbstractMessage
{
    public int $station_id;

    public bool $force = false;

    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_HIGH_PRIORITY;
    }
}
