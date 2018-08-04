<?php
namespace App\Webhook\Connector;

use App\Entity;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

class TuneIn extends AbstractConnector
{
    /**
     * @param array $current_events
     * @param array|null $triggers
     * @return bool
     */
    public function shouldDispatch(array $current_events, array $triggers): bool
    {
        return in_array('song_changed', $current_events);
    }

    /**
     * @param Entity\Station $station
     * @param Entity\Api\NowPlaying $np
     * @param array $config
     */
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np, array $config): void
    {
        if (empty($config['partner_id']) || empty($config['partner_key']) || empty($config['station_id'])) {
            $this->logger->error('Webhook '.get_called_class().' is missing necessary configuration. Skipping...');
            return;
        }

        $this->logger->debug('Dispatching TuneIn AIR API call...');

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://air.radiotime.com',
            'http_errors' => false,
            'timeout' => 2.0,
        ]);

        try {
            $response = $client->get('/Playing.ashx', [
                'query' => [
                    'partnerId' => $config['partner_id'],
                    'partnerKey' => $config['partner_key'],
                    'id' => $config['station_id'],
                    'title' => $np->now_playing->song->title,
                    'artist' => $np->now_playing->song->artist,
                    'album' => $np->now_playing->song->artist,
                ],
            ]);

            $this->logger->debug(
                sprintf('TuneIn returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from TuneIn (%d): %s', $e->getCode(), $e->getMessage()));
        }

    }
}
