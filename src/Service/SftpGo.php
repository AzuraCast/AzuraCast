<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Station;
use App\Environment;

class SftpGo
{
    public static function isSupported(): bool
    {
        $environment = Environment::getInstance();

        return !$environment->isTesting() && $environment->isDockerRevisionAtLeast(7);
    }

    public static function isSupportedForStation(Station $station): bool
    {
        $mediaStorage = $station->getMediaStorageLocation();
        return $mediaStorage->isLocal() && self::isSupported();
    }
}
