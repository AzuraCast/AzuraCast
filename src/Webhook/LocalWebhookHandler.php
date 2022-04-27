<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Entity;
use App\Environment;
use App\Service\NChan;
use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Filesystem\Filesystem;

use const JSON_PRETTY_PRINT;

class LocalWebhookHandler
{
    public const NAME = 'local';

    public function __construct(
        protected Logger $logger,
        protected Client $httpClient,
        protected CacheInterface $cache,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Environment $environment,
    ) {
    }

    public function dispatch(
        Entity\Station $station,
        Entity\Api\NowPlaying\NowPlaying $np
    ): void {
        // Write local static file that the video stream (and other scripts) can use.
        $this->logger->debug('Writing local nowplaying text file...');

        $configDir = $station->getRadioConfigDir();
        $npFile = $configDir . '/nowplaying.txt';

        $npText = implode(
            ' - ',
            array_filter(
                [
                    $np->now_playing->song->artist ?? null,
                    $np->now_playing->song->title ?? null,
                ]
            )
        );

        if (empty($npText)) {
            $npText = $station->getName() ?? '';
        }

        $fsUtils = new Filesystem();

        // Atomic rename to ensure the file is always there.
        $fsUtils->dumpFile($npFile, $npText);

        // Write JSON file to disk so nginx can serve it without calling the PHP stack at all.
        $this->logger->debug('Writing static nowplaying text file...');

        $staticNpDir = $this->environment->getTempDirectory() . '/nowplaying';
        $fsUtils->mkdir($staticNpDir);

        $staticNpPath = $staticNpDir . '/' . $station->getShortName() . '.json';
        $fsUtils->dumpFile(
            $staticNpPath,
            json_encode($np, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: ''
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
