<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Webhook\Enums\WebhookTriggers;

final class TuneIn extends AbstractConnector
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

        if (empty($config['partner_id']) || empty($config['partner_key']) || empty($config['station_id'])) {
            throw $this->incompleteConfigException($webhook);
        }

        $this->logger->debug('Dispatching TuneIn AIR API call...');

        $messageQuery = [
            'partnerId' => $config['partner_id'],
            'partnerKey' => $config['partner_key'],
            'id' => $config['station_id'],
            'title' => $np->now_playing?->song?->title,
            'artist' => $np->now_playing?->song?->artist,
            'album' => $np->now_playing?->song?->album,
        ];

        $response = $this->httpClient->get(
            'https://air.radiotime.com/Playing.ashx',
            [
                'query' => $messageQuery,
            ]
        );

        $this->logHttpResponse(
            $webhook,
            $response,
            $messageQuery
        );
    }
}
