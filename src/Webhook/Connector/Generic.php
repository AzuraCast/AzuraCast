<?php
namespace App\Webhook\Connector;

use App\Entity;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

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
            $this->logger->error('Webhook '.get_called_class().' is missing necessary configuration. Skipping...');
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

            $this->logger->debug(
                sprintf('Generic webhook code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from generic webhook (%d): %s', $e->getCode(), $e->getMessage()));
        }
    }
}
