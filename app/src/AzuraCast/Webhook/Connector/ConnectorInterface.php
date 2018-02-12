<?php
namespace AzuraCast\Webhook\Connector;

use Entity;

interface ConnectorInterface
{
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np_new);
}