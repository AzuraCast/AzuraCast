<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Webhook\Enums\WebhookTriggers;

final class RadioDe extends AbstractConnector
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
            empty($config['broadcastsubdomain'])
            || empty($config['apikey'])
            || empty($config['station_id'])
        ) {
            throw $this->incompleteConfigException($webhook);
        }

        $this->logger->debug('Dispatching RadioDe AIR API call...');

        $messageBody = [
            'broadcastsubdomain' => $config['broadcastsubdomain'],
            'apikey' => $config['apikey'],
            'id' => $config['station_id'],
            'title' => $np->now_playing?->song?->title,
            'artist' => $np->now_playing?->song?->artist,
            'album' => $np->now_playing?->song?->album,
        ];

        $response = $this->httpClient->get(
            'https://api.radio.de/info/v2/pushmetadata/playingsong',
            [
                'query' => $messageBody,
            ]
        );

        $this->logHttpResponse($webhook, $response, $messageBody);
    }
}
