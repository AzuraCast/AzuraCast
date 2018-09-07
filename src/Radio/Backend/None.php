<?php
namespace App\Radio\Backend;

class None extends BackendAbstract
{
    protected $supports_media = false;

    protected $supports_requests = false;

    protected $supports_streamers = false;

    public function read(): bool
    {
    }

    public function write(): bool
    {
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
