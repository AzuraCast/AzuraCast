<?php

namespace App\Radio\Backend;

use App\Entity;
use App\Logger;

class None extends AbstractBackend
{
    public static function supportsMedia(): bool
    {
        return false;
    }

    public static function supportsStreamers(): bool
    {
        return false;
    }

    public static function supportsWebStreaming(): bool
    {
        return false;
    }

    public static function supportsRequests(): bool
    {
        return false;
    }

    public function write(Entity\Station $station): bool
    {
        return true;
    }

    public function isRunning(Entity\Station $station): bool
    {
        return true;
    }

    public function start(Entity\Station $station): void
    {
        Logger::getInstance()->error(
            'Cannot start process; AutoDJ is currently disabled.',
            ['station_id' => $station->getId(), 'station_name' => $station->getName()]
        );
    }
}
