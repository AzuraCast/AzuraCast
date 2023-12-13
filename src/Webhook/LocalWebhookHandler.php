<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Service\Centrifugo;
use Symfony\Component\Filesystem\Filesystem;

use const JSON_PRETTY_PRINT;

final class LocalWebhookHandler
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;

    public const NAME = 'local';

    public function __construct(
        private readonly Centrifugo $centrifugo
    ) {
    }

    public function dispatch(
        Station $station,
        NowPlaying $np,
        array $triggers
    ): void {
        $fsUtils = new Filesystem();

        $staticNpDir = $this->environment->getTempDirectory() . '/nowplaying';
        $fsUtils->mkdir($staticNpDir);

        // Write local static file that the video stream (and other scripts) can use.
        $this->logger->debug('Writing local nowplaying text file...');

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

        $configDir = $station->getRadioConfigDir();
        $npFile = $configDir . '/nowplaying.txt';
        $npStaticFile = $staticNpDir . '/' . $station->getShortName() . '.txt';

        $fsUtils->dumpFile($npFile, $npText);
        $fsUtils->dumpFile($npStaticFile, $npText);

        // Write JSON file to disk so nginx can serve it without calling the PHP stack at all.
        $this->logger->debug('Writing static nowplaying text file...');

        $staticNpPath = $staticNpDir . '/' . $station->getShortName() . '.json';
        $fsUtils->dumpFile(
            $staticNpPath,
            json_encode($np, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: ''
        );

        // Publish to websocket library
        if ($this->centrifugo->isSupported()) {
            $this->centrifugo->publishToStation($station, $np, $triggers);
        }
    }
}
