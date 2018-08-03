<?php
namespace App\Radio\Backend;

abstract class BackendAbstract extends \AzuraCast\Radio\AdapterAbstract
{
    protected $supports_media = true;

    public function supportsMedia()
    {
        return $this->supports_media;
    }

    protected $supports_requests = true;

    public function supportsRequests()
    {
        return $this->supports_requests;
    }

    protected $supports_streamers = true;

    public function supportsStreamers()
    {
        return $this->supports_streamers;
    }

    public function getStreamPort()
    {
        return null;
    }

    public function getProgramName()
    {
        return 'station_' . $this->station->getId() . ':station_' . $this->station->getId() . '_backend';
    }
}
