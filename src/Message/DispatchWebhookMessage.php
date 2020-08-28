<?php
namespace App\Message;

use App\Entity\Api\NowPlaying;

class DispatchWebhookMessage extends AbstractMessage
{
    /** @var int The numeric identifier for the StationWebhook record being processed. */
    public int $webhook_id;

    public NowPlaying $np;

    public bool $is_standalone = true;

    public array $triggers = [];
}
