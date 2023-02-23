<?php

declare(strict_types=1);

namespace App\Message\Meilisearch;

use App\Message\AbstractMessage;

final class UpdatePlaylistsMessage extends AbstractMessage
{
    /** @var int The numeric identifier for the Station entity. */
    public int $station_id;

    /** @var int[]|null Only update for specific media IDs. */
    public ?array $media_ids = null;
}
