<?php
namespace App\Webhook\Connector;

use App\Event\SendWebhooks;

interface ConnectorInterface
{
    /**
     * Return a boolean indicating whether this connector should dispatch, given the current events
     * that are set to be triggered, and the configured triggers for this connector.
     *
     * @param SendWebhooks $event The current webhook dispatching event being evaluated.
     * @param array|null $triggers The configured triggers for this connector.
     * @return bool
     */
    public function shouldDispatch(SendWebhooks $event, array $triggers): bool;

    /**
     * Trigger the webhook for the specified station, now playing entry, and specified configuration.
     *
     * @param SendWebhooks $event The details of the event that triggered the webhook.
     * @param array $config The specific settings associated with this webhook.
     */
    public function dispatch(SendWebhooks $event, array $config): void;
}
