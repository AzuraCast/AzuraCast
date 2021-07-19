<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
use GuzzleHttp\Exception\TransferException;

class TuneIn extends AbstractConnector
{
    public const NAME = 'tunein';

    protected function webhookShouldTrigger(Entity\StationWebhook $webhook, array $triggers = []): bool
    {
        return in_array(Entity\StationWebhook::TRIGGER_SONG_CHANGED, $triggers, true);
    }

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

        if (empty($config['partner_id']) || empty($config['partner_key']) || empty($config['station_id'])) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return false;
        }

        $this->logger->debug('Dispatching TuneIn AIR API call...');

        try {
            $response = $this->httpClient->get(
                'http://air.radiotime.com/Playing.ashx',
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
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from TuneIn (%d): %s', $e->getCode(), $e->getMessage()));
            return false;
        }

        return true;
    }
}
