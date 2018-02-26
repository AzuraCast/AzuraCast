<?php
namespace AzuraCast\Webhook\Connector;

use Entity;
use GuzzleHttp\Exception\TransferException;

class Generic extends AbstractConnector
{
    /**
     * @param Entity\Station $station
     * @param Entity\Api\NowPlaying $np
     * @param array $config
     */
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np, array $config): void
    {
        $webhook_url = $this->_getValidUrl($config['webhook_url'] ?? '');

        if (empty($webhook_url)) {
            \App\Debug::log('Webhook is missing necessary configuration. Skipping...');
            return;
        }

        $client = new \GuzzleHttp\Client([
            'http_errors' => false,
            'timeout' => 2.0,
        ]);

        try {
            $response = $client->request('POST', $webhook_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $np,
            ]);

            \App\Debug::log(sprintf('Generic webhook returned code %d', $response->getStatusCode()));
        } catch(TransferException $e) {
            \App\Debug::log(sprintf('Error from generic webhook (%d): %s', $e->getCode(), $e->getMessage()));
        }
    }
}