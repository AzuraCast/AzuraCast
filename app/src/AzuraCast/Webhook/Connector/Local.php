<?php
namespace AzuraCast\Webhook\Connector;

use App\Cache;
use Entity;
use InfluxDB\Database;

class Local extends AbstractConnector
{
    /** @var Database */
    protected $influx;

    /** @var Cache */
    protected $cache;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    public function __construct(Database $influx, Cache $cache, Entity\Repository\SettingsRepository $settings_repo)
    {
        $this->influx = $influx;
        $this->cache = $cache;
        $this->settings_repo = $settings_repo;
    }

    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np)
    {
        \App\Debug::log('Writing entry to InfluxDB...');

        // Post statistics to InfluxDB.
        $influx_point = new \InfluxDB\Point(
            'station.' . $station->getId() . '.listeners',
            (int)$np->listeners->current,
            [],
            ['station' => $station->getId()],
            time()
        );

        $this->influx->writePoints([$influx_point], \InfluxDB\Database::PRECISION_SECONDS);

        // Replace the relevant station information in the cache and database.
        \App\Debug::log('Updating NowPlaying cache...');

        $np_full = $this->cache->get('api_nowplaying_data');

        if ($np_full) {
            foreach($np_full as &$np_row) {
                /** @var Entity\Api\NowPlaying $np_row */
                if ($np_row->station->id === $station->getId()) {
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

            \App\Debug::print_r($np_full);
        }
    }
}