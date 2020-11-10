<?php

namespace App\Service;

use App\Entity\Station;
use App\Settings;

class SftpGo
{
    public static function isSupported(): bool
    {
        $settings = Settings::getInstance();

        return !$settings->isTesting() && $settings->isDockerRevisionNewerThan(7);
    }

    public static function isSupportedForStation(Station $station): bool
    {
        $mediaStorage = $station->getMediaStorageLocation();
        return $mediaStorage->isLocal() && self::isSupported();
    }
}
