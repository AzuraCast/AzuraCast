<?php

namespace App\Message;

use App\MessageQueue\QueueManager;

class ReprocessMediaMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StationMedia record being processed. */
    public int $media_id;

    /** @var bool Whether to force reprocessing even if checks indicate it is not necessary. */
    public bool $force = false;

    public function getIdentifier(): string
    {
        return 'ReprocessMediaMessage_' . $this->media_id;
    }

    public function getQueue(): string
    {
        return QueueManager::QUEUE_MEDIA;
    }
}
