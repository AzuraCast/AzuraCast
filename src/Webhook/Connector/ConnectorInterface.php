<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;

interface ConnectorInterface
{
    /**
     * Return a boolean indicating whether this connector should dispatch, given the current events
     * that are set to be triggered, and the configured triggers for this connector.
     *
     * @param Entity\StationWebhook $webhook
     * @param array<string> $triggers
     *
     * @return bool Whether the given webhook should dispatch with these triggers.
     */
    public function shouldDispatch(
        Entity\StationWebhook $webhook,
        array $triggers = []
    ): bool;

    /**
     * Trigger the webhook for the specified station, now playing entry, and specified configuration.
     *
     * @param Entity\Station $station
     * @param Entity\StationWebhook $webhook
     * @param Entity\Api\NowPlaying $np
     * @param array<string> $triggers
     *
     * @return bool Whether the webhook actually dispatched.
     */
    public function dispatch(
        Entity\Station $station,
        Entity\StationWebhook $webhook,
        Entity\Api\NowPlaying $np,
        array $triggers
    ): bool;
}
