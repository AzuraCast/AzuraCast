<?php

namespace App\Message;

use App\Entity\Api\NowPlaying;
use App\MessageQueue\QueueManager;

class DispatchWebhookMessage extends AbstractUniqueMessage
{
    public int $station_id;

    public NowPlaying $np;

    public array $triggers = [];

    public function getIdentifier(): string
    {
        return 'DispatchWebhookMessage_' . $this->station_id;
    }

    public function getQueue(): string
    {
        return QueueManager::QUEUE_HIGH_PRIORITY;
    }
}
