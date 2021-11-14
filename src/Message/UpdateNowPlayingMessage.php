<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueManagerInterface;

class UpdateNowPlayingMessage extends AbstractMessage
{
    public int $station_id;

    /** @var int|null The Entity\StationQueue ID for the upcoming track, if it's provided. */
    public ?int $upcoming_track_id;

    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_HIGH_PRIORITY;
    }
}
