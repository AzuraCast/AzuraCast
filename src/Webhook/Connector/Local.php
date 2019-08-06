<?php
namespace App\Webhook\Connector;

use App\Entity;
use App\Event\SendWebhooks;
use App\Service\NChan;
use Azura\Cache;
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
        $station = $event->getStation();

        if ($event->isStandalone()) {
            $this->logger->debug('Writing entry to InfluxDB...');

            // Post statistics to InfluxDB.
            $influx_point = new \InfluxDB\Point(
                'station.' . $station->getId() . '.listeners',
                (int)$np->listeners->current,
                [],
                ['station' => $station->getId()],
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
                    if ($np_old->station->id === $station->getId()) {
                        $np_new[] = $np;
                    } else {
                        $np_new[] = $np_old;
                    }
                }

                $this->cache->save($np_new, 'api_nowplaying_data', 120);
                $this->settings_repo->setSetting('nowplaying', $np_new);
            }
        }

        // Write local static file that the video stream (and other scripts) can use.
        $this->logger->debug('Writing local nowplaying text file...');

        $config_dir = $station->getRadioConfigDir();
        $np_file = $config_dir.'/nowplaying.txt';

        $np_text = implode(' - ', array_filter([
            $np->now_playing->song->artist ?? null,
            $np->now_playing->song->title ?? null,
        ]));

        if (empty($np_text)) {
            $np_text = $station->getName();
        }

        // Atomic rename to ensure the file is always there.
        file_put_contents($np_file.'.new', $np_text);
        rename($np_file.'.new', $np_file);

        // Write JSON file to disk so nginx can serve it without calling the PHP stack at all.
        $this->logger->debug('Writing static nowplaying text file...');

        $static_np_dir = APP_INCLUDE_TEMP.'/nowplaying';
        if (!is_dir($static_np_dir) && !mkdir($static_np_dir) && !is_dir($static_np_dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $static_np_dir));
        }

        $static_path = $static_np_dir.'/'.$station->getShortName().'.json';
        file_put_contents($static_path, json_encode($np, \JSON_PRETTY_PRINT));

        // Send Nchan notification.
        if (NChan::isSupported()) {
            $this->logger->debug('Dispatching Nchan notification...');

            $this->http_client->post('http://localhost:9010/pub/'.urlencode($station->getShortName()), [
                'json' => $np,
            ]);
        }
    }
}
