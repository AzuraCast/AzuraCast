<?php

declare(strict_types=1);

namespace App\Radio;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Station;
use App\Flysystem\StationFilesystems;

final class FallbackFile
{
    use EnvironmentAwareTrait;

    public function getFallbackPathForStation(Station $station): string
    {
        $stationFallback = $station->getFallbackPath();
        if (!empty($stationFallback)) {
            $fsConfig = StationFilesystems::buildConfigFilesystem($station);
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
