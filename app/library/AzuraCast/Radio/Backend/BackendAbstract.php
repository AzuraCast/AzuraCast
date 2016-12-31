<?php
namespace AzuraCast\Radio\Backend;

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

    public function log($message, $class = 'info')
    {
        if (!empty(trim($message)))
            parent::log(str_pad('Radio Backend: ', 20, ' ', STR_PAD_RIGHT).$message, $class);
    }

    public function getProgramName()
    {
        return 'station_'.$this->station->id.':station_'.$this->station->id.'_backend';
    }
}