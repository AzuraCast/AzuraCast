<?php

declare(strict_types=1);

namespace App\Message\Meilisearch;

use App\Message\AbstractMessage;
use App\MessageQueue\QueueNames;

final class AddMediaMessage extends AbstractMessage
{
    /** @var int The numeric identifier for the StorageLocation entity. */
    public int $storage_location_id;

    /** @var int[] An array of media IDs to process. */
    public array $media_ids;

    /** @var bool Whether to include playlist data. */
    public bool $include_playlists = false;

    public function getQueue(): QueueNames
    {
        return QueueNames::SearchIndex;
    }
}
