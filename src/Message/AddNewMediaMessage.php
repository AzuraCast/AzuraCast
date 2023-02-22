<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueNames;

final class AddNewMediaMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StorageLocation entity. */
    public int $storage_location_id;

    /** @var string The relative path for the media file to be processed. */
    public string $path;

    public function getQueue(): QueueNames
    {
        return QueueNames::Media;
    }
}
