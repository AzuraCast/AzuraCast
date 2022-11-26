<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Enums\WebhookTriggers;
use App\Entity\Station;
use App\Entity\StationWebhook;

final class TuneIn extends AbstractConnector
{
    public const NAME = 'tunein';

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
            throw $this->incompleteConfigException(self::NAME);
        }

        $this->logger->debug('Dispatching TuneIn AIR API call...');

        $response = $this->httpClient->get(
            'https://air.radiotime.com/Playing.ashx',
            [
                'query' => [
                    'partnerId' => $config['partner_id'],
                    'partnerKey' => $config['partner_key'],
                    'id' => $config['station_id'],
                    'title' => $np->now_playing?->song?->title,
                    'artist' => $np->now_playing?->song?->artist,
                    'album' => $np->now_playing?->song?->album,
                ],
            ]
        );

        $this->logger->debug(
            sprintf('TuneIn returned code %d', $response->getStatusCode()),
            ['response_body' => $response->getBody()->getContents()]
        );
    }
}
