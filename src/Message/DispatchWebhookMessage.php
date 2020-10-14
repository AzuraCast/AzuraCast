<?php

namespace App\Message;

use App\Entity\Api\NowPlaying;
use App\MessageQueue\QueueManager;

class DispatchWebhookMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StationWebhook record being processed. */
    public int $webhook_id;

    public NowPlaying $np;

    public bool $is_standalone = true;

    public array $triggers = [];

    public function getIdentifier(): string
    {
        return 'DispatchWebhookMessage_' . $this->webhook_id;
    }

    public function getQueue(): string
    {
        return QueueManager::QUEUE_HIGH_PRIORITY;
    }
}
