<?php
namespace App\Webhook\Connector;

use Azura\Cache;
use App\Entity;
use App\Event\SendWebhooks;
use GuzzleHttp\Client;
use InfluxDB\Database;
use Monolog\Logger;

class Local
{
    public const NAME = 'local';

    /** @var Client */
    protected $http_client;

    /** @var Logger */
    protected $logger;

    /** @var Database */
    protected $influx;

    /** @var Cache */
    protected $cache;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    public function __construct(Logger $logger, Client $http_client, Database $influx, Cache $cache, Entity\Repository\SettingsRepository $settings_repo)
    {
        $this->logger = $logger;
        $this->http_client = $http_client;
        $this->influx = $influx;
        $this->cache = $cache;
        $this->settings_repo = $settings_repo;
    }

    public function dispatch(SendWebhooks $event): void
    {
        $np = $event->getNowPlaying();

        $this->logger->debug('Writing entry to InfluxDB...');

        // Post statistics to InfluxDB.
        $influx_point = new \InfluxDB\Point(
            'station.' . $event->getStation()->getId() . '.listeners',
            (int)$np->listeners->current,
            [],
            ['station' => $event->getStation()->getId()],
            time()
        );

        $this->influx->writePoints([$influx_point], Database::PRECISION_SECONDS);

        // Replace the relevant station information in the cache and database.
        $this->logger->debug('Updating NowPlaying cache...');

        $np_full = $this->cache->get('api_nowplaying_data');

        if ($np_full) {
            $np_new = [];
            foreach($np_full as $np_old) {
                /** @var Entity\Api\NowPlaying $np_old */
                if ($np_old->station->id === $event->getStation()->getId()) {
                    $np_new[] = $np;
                } else {
                    $np_new[] = $np_old;
                }
            }

            $this->cache->save($np_new, 'api_nowplaying_data', 120);
            $this->settings_repo->setSetting('nowplaying', $np_new);
        }
    }
}
