<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity;
use App\Environment;
use App\Flysystem\StationFilesystems;

final class FallbackFile
{
    public function __construct(
        private readonly Environment $environment
    ) {
    }

    public function getFallbackPathForStation(Entity\Station $station): string
    {
        $stationFallback = $station->getFallbackPath();
        if (!empty($stationFallback)) {
            $fsConfig = (new StationFilesystems($station))->getConfigFilesystem();
            if ($fsConfig->fileExists($stationFallback)) {
                return $fsConfig->getLocalPath($stationFallback);
            }
        }

        return $this->getDefaultFallbackPath();
    }

    public function getDefaultFallbackPath(): string
    {
        return $this->environment->isDocker()
            ? '/usr/local/share/icecast/web/error.mp3'
            : $this->environment->getBaseDirectory() . '/resources/error.mp3';
    }
}
