<?php

namespace App\Webhook;

use App\Entity;
use App\Event\SendWebhooks;
use App\Service\NChan;
use App\Settings;
use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

use const JSON_PRETTY_PRINT;

class LocalWebhookHandler
{
    public const NAME = 'local';

    protected Client $httpClient;

    protected Logger $logger;

    protected CacheInterface $cache;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    public function __construct(
        Logger $logger,
        Client $httpClient,
        CacheInterface $cache,
        Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->settingsRepo = $settingsRepo;
    }

    public function dispatch(SendWebhooks $event): void
    {
        $np = $event->getNowPlaying();
        $station = $event->getStation();

        if ($event->isStandalone()) {
            // Replace the relevant station information in the cache and database.
            $this->logger->debug('Updating NowPlaying cache...');

            $np_full = $this->cache->get('api_nowplaying_data');

            if ($np_full) {
                $np_new = [];
                foreach ($np_full as $np_old) {
                    /** @var Entity\Api\NowPlaying $np_old */
                    if ($np_old->station->id === $station->getId()) {
                        $np_new[] = $np;
                    } else {
                        $np_new[] = $np_old;
                    }
                }

                $this->cache->set('api_nowplaying_data', $np_new, 120);
                $this->settingsRepo->setSetting('nowplaying', $np_new);
            }
        }

        // Write local static file that the video stream (and other scripts) can use.
        $this->logger->debug('Writing local nowplaying text file...');

        $config_dir = $station->getRadioConfigDir();
        $np_file = $config_dir . '/nowplaying.txt';

        $np_text = implode(' - ', array_filter([
            $np->now_playing->song->artist ?? null,
            $np->now_playing->song->title ?? null,
        ]));

        if (empty($np_text)) {
            $np_text = $station->getName();
        }

        // Atomic rename to ensure the file is always there.
        file_put_contents($np_file . '.new', $np_text);
        rename($np_file . '.new', $np_file);

        // Write JSON file to disk so nginx can serve it without calling the PHP stack at all.
        $this->logger->debug('Writing static nowplaying text file...');

        $static_np_dir = Settings::getInstance()->getTempDirectory() . '/nowplaying';
        if (!is_dir($static_np_dir) && !mkdir($static_np_dir) && !is_dir($static_np_dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $static_np_dir));
        }

        $static_path = $static_np_dir . '/' . $station->getShortName() . '.json';
        file_put_contents(
            $static_path,
            json_encode($np, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Send Nchan notification.
        if (NChan::isSupported()) {
            $this->logger->debug('Dispatching Nchan notification...');

            $this->httpClient->post('http://localhost:9010/pub/' . urlencode($station->getShortName()), [
                'json' => $np,
            ]);
        }
    }
}
