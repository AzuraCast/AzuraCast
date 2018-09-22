<?php
namespace App\Radio\Backend;

use App\Entity;

class None extends BackendAbstract
{
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
        $this->logger->error('Cannot start process; AutoDJ is currently disabled.', ['station_id' => $station->getId(), 'station_name' => $station->getName()]);
    }

    public static function supportsMedia(): bool
    {
        return false;
    }

    public static function supportsStreamers(): bool
    {
        return false;
    }

    public static function supportsRequests(): bool
    {
        return false;
    }
}
