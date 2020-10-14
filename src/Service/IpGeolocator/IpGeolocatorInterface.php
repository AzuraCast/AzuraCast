<?php

namespace App\Service\IpGeolocator;

use MaxMind\Db\Reader;

interface IpGeolocatorInterface
{
    public static function getDatabasePath(): string;

    public static function isAvailable(): bool;

    public static function getReader(): ?Reader;

    public static function getAttribution(): string;

    public static function getVersion(): ?string;
}
