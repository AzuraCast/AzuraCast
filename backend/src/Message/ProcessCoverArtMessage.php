<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueNames;

final class ProcessCoverArtMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StorageLocation entity. */
    public int $storage_location_id;

    /** @var string The relative path for the cover file to be processed. */
    public string $path;

    /** @var string The hash of the folder (used for storing and indexing the cover art). */
    public string $folder_hash;

    public function getQueue(): QueueNames
    {
        return QueueNames::Media;
    }
}
