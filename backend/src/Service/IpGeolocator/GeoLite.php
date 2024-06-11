<?php

declare(strict_types=1);

namespace App\Service\IpGeolocator;

use App\Environment;
use App\Utilities\File;

final class GeoLite extends AbstractIpGeolocator
{
    public static function getReaderShortName(): string
    {
        return 'geolite';
    }

    public static function getBaseDirectory(): string
    {
        $parentDir = Environment::getInstance()->getParentDirectory();

        return File::getFirstExistingDirectory([
            $parentDir . '/geoip',
            $parentDir . '/storage/geoip',
        ]);
    }

    public static function getDatabasePath(): string
    {
        return self::getBaseDirectory() . '/GeoLite2-City.mmdb';
    }

    public static function getAttribution(): string
    {
        return sprintf(
            __('This product includes GeoLite2 data created by MaxMind, available from %s.'),
            '<a href="https://www.maxmind.com">https://www.maxmind.com</a>'
        );
    }
}
