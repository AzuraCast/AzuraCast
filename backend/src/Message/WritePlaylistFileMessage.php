<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueNames;

final class WritePlaylistFileMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StationPlaylist record being processed. */
    public int $playlist_id;

    public function getIdentifier(): string
    {
        return 'WritePlaylistFileMessage_' . $this->playlist_id;
    }

    public function getQueue(): QueueNames
    {
        return QueueNames::LowPriority;
    }
}
