<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Types;

final class Generic extends AbstractConnector
{
    /**
     * @inheritDoc
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->getConfig();

        $webhookUrl = $this->getValidUrl($config['webhook_url']);

        if (empty($webhookUrl)) {
            throw $this->incompleteConfigException($webhook);
        }

        $requestOptions = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $np,
            'timeout' => Types::floatOrNull($config['timeout']) ?? 5.0,
        ];

        if (!empty($config['basic_auth_username']) && !empty($config['basic_auth_password'])) {
            $requestOptions['auth'] = [
                $config['basic_auth_username'],
                $config['basic_auth_password'],
            ];
        }

        $response = $this->httpClient->request('POST', $webhookUrl, $requestOptions);

        $this->logHttpResponse(
            $webhook,
            $response
        );
    }
}
