<?php
namespace App\Radio\Backend;

abstract class BackendAbstract extends \App\Radio\AdapterAbstract
{
    public function supportsMedia(): bool
    {
        return true;
    }

    public function supportsRequests(): bool
    {
        return true;
    }

    public function supportsStreamers(): bool
    {
        return true;
    }

    public function getStreamPort()
    {
        return null;
    }

    public function getProgramName(): string
    {
        return 'station_' . $this->station->getId() . ':station_' . $this->station->getId() . '_backend';
    }
}
