<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueManagerInterface;

class ReprocessPodcastMediaMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the PodcastMedia record being processed. */
    public int $podcastMediaId;

    /** @var bool Whether to force reprocessing even if checks indicate it is not necessary. */
    public bool $force = false;

    public function getIdentifier(): string
    {
        return 'ReprocessPodcastMediaMessage_' . $this->podcastMediaId;
    }

    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_PODCAST_MEDIA;
    }
}
