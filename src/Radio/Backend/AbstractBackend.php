<?php

namespace App\Radio\Backend;

use App\Entity;
use App\Radio\AbstractAdapter;

abstract class AbstractBackend extends AbstractAdapter
{
    public static function supportsMedia(): bool
    {
        return true;
    }

    public static function supportsRequests(): bool
    {
        return true;
    }

    public static function supportsStreamers(): bool
    {
        return true;
    }

    public static function supportsWebStreaming(): bool
    {
        return true;
    }

    public function getStreamPort(Entity\Station $station): ?int
    {
        return null;
    }

    /**
     * @return mixed[]
     */
    public function annotateMedia(Entity\StationMedia $media): array
    {
        return [];
    }

    public function getProgramName(Entity\Station $station): string
    {
        return 'station_' . $station->getId() . ':station_' . $station->getId() . '_backend';
    }
}
