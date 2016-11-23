<?php
namespace App\Radio\Backend;

abstract class BackendAbstract extends \App\Radio\AdapterAbstract
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
}