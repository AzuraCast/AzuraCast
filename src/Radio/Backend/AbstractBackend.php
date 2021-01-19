<?php

namespace App\Radio\Backend;

use App\Entity;
use App\Radio\AbstractAdapter;

abstract class AbstractBackend extends AbstractAdapter
{
    public function supportsMedia(): bool
    {
        return false;
    }

    public function supportsRequests(): bool
    {
        return false;
    }

    public function supportsStreamers(): bool
    {
        return false;
    }

    public function supportsWebStreaming(): bool
    {
        return false;
    }

    public function getStreamPort(Entity\Station $station): ?int
    {
        return null;
    }

    /**
     * @param Entity\StationMedia $media
     *
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
