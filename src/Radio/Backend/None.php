<?php
namespace App\Radio\Backend;

class None extends BackendAbstract
{
    public function supportsMedia(): bool
    {
        return false;
    }

    public function supportsStreamers(): bool
    {
        return false;
    }

    public function supportsRequests(): bool
    {
        return false;
    }

    public function write(): bool
    {
        return true;
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function start(): void
    {
        $this->logger->error('Cannot start process; AutoDJ is currently disabled.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName()]);
    }
}
