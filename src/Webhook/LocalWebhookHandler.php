<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Entity;
use App\Environment;
use App\Service\NChan;
use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

use const JSON_PRETTY_PRINT;

class LocalWebhookHandler
{
    public const NAME = 'local';

    public function __construct(
        protected Logger $logger,
        protected Client $httpClient,
        protected CacheInterface $cache,
        protected Entity\Repository\SettingsRepository $settingsRepo
    ) {
    }

    public function dispatch(
        Entity\Station $station,
        Entity\Api\NowPlaying $np
    ): void {
        // Write local static file that the video stream (and other scripts) can use.
        $this->logger->debug('Writing local nowplaying text file...');

        $config_dir = $station->getRadioConfigDir();
        $np_file = $config_dir . '/nowplaying.txt';

        $np_text = implode(
            ' - ',
            array_filter(
                [
                    $np->now_playing->song->artist ?? null,
                    $np->now_playing->song->title ?? null,
                ]
            )
        );

        if (empty($np_text)) {
            $np_text = $station->getName();
        }

        // Atomic rename to ensure the file is always there.
        file_put_contents($np_file . '.new', $np_text);
        rename($np_file . '.new', $np_file);

        // Write JSON file to disk so nginx can serve it without calling the PHP stack at all.
        $this->logger->debug('Writing static nowplaying text file...');

        $static_np_dir = Environment::getInstance()->getTempDirectory() . '/nowplaying';
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

            $this->httpClient->post(
                'http://localhost:9010/pub/' . urlencode($station->getShortName()),
                [
                    'json' => $np,
                ]
            );
        }
    }
}
