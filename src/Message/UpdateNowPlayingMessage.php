<?php

namespace App\Message;

use App\MessageQueue\QueueManager;

class UpdateNowPlayingMessage extends AbstractMessage
{
    public int $station_id;

    public function getQueue(): string
    {
        return QueueManager::QUEUE_HIGH_PRIORITY;
    }
}
