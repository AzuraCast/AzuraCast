<?php
namespace AzuraCast\Webhook\Connector;

use Entity;

class Local extends AbstractConnector
{
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np_new)
    {
        $base_url = (APP_INSIDE_DOCKER) ? 'nginx' : 'localhost';
        $channel_url = 'http://'.$base_url.':9010/pub/'.urlencode($station->getId());

        $shell_cmd = 'sleep 10; curl --request POST --data '.escapeshellarg(json_encode($np_new)).' '.$channel_url;
        shell_exec(sprintf('(%s) > /dev/null 2>&1 &', $shell_cmd));
    }
}