<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\MessageQueue\QueueManagerInterface;

final class DispatchWebhookMessage extends AbstractUniqueMessage
{
    public int $station_id;

    public NowPlaying $np;

    /** @var array<string> */
    public array $triggers = [];

    public function getIdentifier(): string
    {
        return 'DispatchWebhookMessage_' . $this->station_id;
    }

    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_HIGH_PRIORITY;
    }
}
