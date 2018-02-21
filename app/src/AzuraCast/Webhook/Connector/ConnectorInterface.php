<?php
namespace AzuraCast\Webhook\Connector;

use Entity;

interface ConnectorInterface
{
    /**
     * Return a boolean indicating whether this connector should dispatch, given the current events
     * that are set to be triggered, and the configured triggers for this connector.
     *
     * @param array $current_events The events that are currently being triggered.
     * @param array|null $triggers The configured triggers for this connector.
     * @return bool
     */
    public function shouldDispatch(array $current_events, array $triggers): bool;

    /**
     * Trigger the webhook for the specified station, now playing entry, and specified configuration.
     *
     * @param Entity\Station $station
     * @param Entity\Api\NowPlaying $np_new
     * @param array $config
     */
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np_new, array $config): void;
}