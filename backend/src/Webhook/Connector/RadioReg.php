<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Webhook\Enums\WebhookTriggers;

final class RadioReg extends AbstractConnector
{
    protected function webhookShouldTrigger(StationWebhook $webhook, array $triggers = []): bool
    {
        return in_array(WebhookTriggers::SongChanged->value, $triggers, true);
    }

    /**
     * @optischa
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->config ?? [];

        if (
            empty($config['apikey']) || empty($config['webhookurl'])
        ) {
            throw $this->incompleteConfigException($webhook);
        }

        $this->logger->debug('Dispatching RadioReg API call...');

        $messageBody = [
            'title' => $np->now_playing?->song?->title,
            'artist' => $np->now_playing?->song?->artist,
        ];

        $response = $this->httpClient->post(
            $config['webhookurl'],
            [
                'json' => $messageBody,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $config['apikey'],
                ],
            ],
        );

        $this->logHttpResponse($webhook, $response, $messageBody);
    }
}
