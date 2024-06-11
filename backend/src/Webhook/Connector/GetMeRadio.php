<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Webhook\Enums\WebhookTriggers;

final class GetMeRadio extends AbstractConnector
{
    protected function webhookShouldTrigger(StationWebhook $webhook, array $triggers = []): bool
    {
        return in_array(WebhookTriggers::SongChanged->value, $triggers, true);
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
        $config = $webhook->getConfig();

        if (
            empty($config['token'])
            || empty($config['station_id'])
        ) {
            throw $this->incompleteConfigException($webhook);
        }

        $this->logger->debug('Dispatching GetMeRadio API call...');

        $messageBody = [
            'token' => $config['token'],
            'station_id' => $config['station_id'],
            'title' => $np->now_playing?->song?->title,
            'artist' => $np->now_playing?->song?->artist,
        ];

        $response = $this->httpClient->get(
            'https://services.getmeradio.com/api/song_update/',
            [
                'query' => $messageBody,
            ]
        );

        $this->logHttpResponse($webhook, $response, $messageBody);
    }
}
