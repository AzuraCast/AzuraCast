<?php

namespace App\Service\IpGeolocator;

use App\Settings;

class GeoLite extends AbstractIpGeolocator
{
    public static function getBaseDirectory(): string
    {
        $settings = Settings::getInstance();
        return dirname($settings[Settings::BASE_DIR]) . '/geoip';
    }

    public static function getDatabasePath(): string
    {
        return self::getBaseDirectory() . '/GeoLite2-City.mmdb';
    }

    public static function getAttribution(): string
    {
        return __(
            'This product includes GeoLite2 data created by MaxMind, available from %s.',
            '<a href="http://www.maxmind.com">http://www.maxmind.com</a>'
        );
    }
}
