<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueManagerInterface;

class AddNewPodcastMediaMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StorageLocation entity. */
    public int $storageLocationId;

    /** @var string The relative path for the podcast media file to be processed. */
    public string $path;

    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_PODCAST_MEDIA;
    }
}
