<?php

namespace App\Message;

use App\MessageQueue\QueueManager;

class WritePlaylistFileMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StationPlaylist record being processed. */
    public int $playlist_id;

    public function getIdentifier(): string
    {
        return 'WritePlaylistFileMessage_' . $this->playlist_id;
    }

    public function getQueue(): string
    {
        return QueueManager::QUEUE_LOW_PRIORITY;
    }
}
