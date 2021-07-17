<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
use GuzzleHttp\Exception\TransferException;

class Generic extends AbstractConnector
{
    public const NAME = 'generic';

    /**
     * @inheritDoc
     */
    public function dispatch(
        Entity\Station $station,
        Entity\StationWebhook $webhook,
        Entity\Api\NowPlaying $np,
        array $triggers
    ): bool {
        $config = $webhook->getConfig();

        $webhook_url = $this->getValidUrl($config['webhook_url'] ?? '');

        if (empty($webhook_url)) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return false;
        }

        try {
            $request_options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $np,
            ];

            if (!empty($config['basic_auth_username']) && !empty($config['basic_auth_password'])) {
                $request_options['auth'] = [
                    $config['basic_auth_username'],
                    $config['basic_auth_password'],
                ];
            }

            $response = $this->httpClient->request('POST', $webhook_url, $request_options);

            $this->logger->debug(
                sprintf('Generic webhook returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from generic webhook (%d): %s', $e->getCode(), $e->getMessage()));
            return false;
        }

        return true;
    }
}
