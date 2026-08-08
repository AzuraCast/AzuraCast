<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Types;
use App\Utilities\UserUrlFilter;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class Generic extends AbstractConnector
{
    public function __construct(
        Client $httpClient,
        private readonly UserUrlFilter $userUrlFilter
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->config ?? [];

        $webhookUrl = $this->userUrlFilter->filterSensitiveUserUrl(
            $config['webhook_url'],
            'Generic Webhook'
        );

        if (empty($webhookUrl)) {
            throw $this->incompleteConfigException($webhook);
        }

        $requestOptions = [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => $np,
            RequestOptions::TIMEOUT => Types::floatOrNull($config['timeout']) ?? 5.0,
        ];

        if (!empty($config['basic_auth_username']) && !empty($config['basic_auth_password'])) {
            $requestOptions[RequestOptions::AUTH] = [
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
