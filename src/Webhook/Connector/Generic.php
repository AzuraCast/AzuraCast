<?php

namespace App\Webhook\Connector;

use App\Entity\StationWebhook;
use App\Event\SendWebhooks;
use GuzzleHttp\Exception\TransferException;

class Generic extends AbstractConnector
{
    public const NAME = 'generic';

    public function dispatch(SendWebhooks $event, StationWebhook $webhook): void
    {
        $config = $webhook->getConfig();

        $webhook_url = $this->getValidUrl($config['webhook_url'] ?? '');

        if (empty($webhook_url)) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return;
        }

        try {
            $request_options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $event->getNowPlaying(),
            ];

            if (!empty($config['basic_auth_username']) && !empty($config['basic_auth_password'])) {
                $request_options['auth'] = [
                    $config['basic_auth_username'],
                    $config['basic_auth_password'],
                ];
            }

            $response = $this->http_client->request('POST', $webhook_url, $request_options);

            $this->logger->debug(
                sprintf('Generic webhook returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from generic webhook (%d): %s', $e->getCode(), $e->getMessage()));
        }
    }
}
