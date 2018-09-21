<?php
namespace App\Webhook\Connector;

use App\Cache;
use App\Entity;
use App\Event\SendWebhooks;
use GuzzleHttp\Client;
use InfluxDB\Database;
use Monolog\Logger;

class Local extends AbstractConnector
{
    /** @var Database */
    protected $influx;

    /** @var Cache */
    protected $cache;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    public function __construct(Logger $logger, Client $http_client, Database $influx, Cache $cache, Entity\Repository\SettingsRepository $settings_repo)
    {
        parent::__construct($logger, $http_client);

        $this->influx = $influx;
        $this->cache = $cache;
        $this->settings_repo = $settings_repo;
    }

    public function shouldDispatch(SendWebhooks $event, array $triggers): bool
    {
        return true;
    }

    public function dispatch(SendWebhooks $event, array $config): void
    {
        $this->logger->debug('Writing entry to InfluxDB...');

        // Post statistics to InfluxDB.
        $influx_point = new \InfluxDB\Point(
            'station.' . $event->getStation()->getId() . '.listeners',
            (int)$np->listeners->current,
            [],
            ['station' => $event->getStation()->getId()],
            time()
        );

        $this->influx->writePoints([$influx_point], \InfluxDB\Database::PRECISION_SECONDS);

        // Replace the relevant station information in the cache and database.
        $this->logger->debug('Updating NowPlaying cache...');

        $np_full = $this->cache->get('api_nowplaying_data');

        if ($np_full) {
            foreach($np_full as &$np_row) {
                /** @var Entity\Api\NowPlaying $np_row */
                if ($np_row->station->id === $event->getStation()->getId()) {
                    $np_row = $np;
                }

                $np_row->cache = 'hit';
            }
            unset($np_row);

            $this->cache->save($np_full, 'api_nowplaying_data', 120);

            foreach ($np_full as &$np_row) {
                /** @var Entity\Api\NowPlaying $np_row */
                $np_row->cache = 'database';
            }
            unset($np_row);

            $this->settings_repo->setSetting('nowplaying', $np_full);
        }
    }
}
