<?php

namespace App\Webhook\Connector;

use App\Entity\StationWebhook;
use App\Event\SendWebhooks;
use GuzzleHttp\Exception\TransferException;

class TuneIn extends AbstractConnector
{
    public const NAME = 'tunein';

    public function shouldDispatch(SendWebhooks $event, StationWebhook $webhook): bool
    {
        return $event->hasTrigger('song_changed');
    }

    public function dispatch(SendWebhooks $event, StationWebhook $webhook): void
    {
        $config = $webhook->getConfig();

        if (empty($config['partner_id']) || empty($config['partner_key']) || empty($config['station_id'])) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return;
        }

        $this->logger->debug('Dispatching TuneIn AIR API call...');

        try {
            $np = $event->getNowPlaying();

            $response = $this->http_client->get('http://air.radiotime.com/Playing.ashx', [
                'query' => [
                    'partnerId' => $config['partner_id'],
                    'partnerKey' => $config['partner_key'],
                    'id' => $config['station_id'],
                    'title' => $np->now_playing->song->title,
                    'artist' => $np->now_playing->song->artist,
                    'album' => $np->now_playing->song->album,
                ],
            ]);

            $this->logger->debug(
                sprintf('TuneIn returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from TuneIn (%d): %s', $e->getCode(), $e->getMessage()));
        }
    }
}
