<?php

declare(strict_types=1);

namespace App\Service\IpGeolocator;

use Carbon\CarbonImmutable;
use MaxMind\Db\Reader;

abstract class AbstractIpGeolocator implements IpGeolocatorInterface
{
    public static function isAvailable(): bool
    {
        $databasePath = static::getDatabasePath();

        return file_exists($databasePath);
    }

    public static function getReader(): ?Reader
    {
        $databasePath = static::getDatabasePath();

        if (file_exists($databasePath)) {
            return new Reader($databasePath);
        }
        return null;
    }

    public static function getVersion(): ?string
    {
        if (null === ($reader = self::getReader())) {
            return null;
        }

        $metadata = $reader->metadata();

        $buildDate = CarbonImmutable::createFromTimestampUTC($metadata->buildEpoch);
        return $metadata->databaseType . ' (' . $buildDate->format('Y-m-d') . ')';
    }
}
