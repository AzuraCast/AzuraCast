<?php

namespace App\Webhook\Connector;

use App\Entity\StationWebhook;
use App\Event\SendWebhooks;

interface ConnectorInterface
{
    /**
     * Return a boolean indicating whether this connector should dispatch, given the current events
     * that are set to be triggered, and the configured triggers for this connector.
     *
     * @param SendWebhooks $event The current webhook dispatching event being evaluated.
     * @param StationWebhook $webhook
     */
    public function shouldDispatch(SendWebhooks $event, StationWebhook $webhook): bool;

    /**
     * Trigger the webhook for the specified station, now playing entry, and specified configuration.
     *
     * @param SendWebhooks $event The details of the event that triggered the webhook.
     * @param StationWebhook $webhook
     */
    public function dispatch(SendWebhooks $event, StationWebhook $webhook): void;
}
