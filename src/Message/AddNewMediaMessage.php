<?php

namespace App\Message;

use App\MessageQueue\QueueManager;

class AddNewMediaMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StorageLocation entity. */
    public int $storage_location_id;

    /** @var string The relative path for the media file to be processed. */
    public string $path;

    public function getQueue(): string
    {
        return QueueManager::QUEUE_MEDIA;
    }
}
