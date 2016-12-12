<?php
namespace App\Radio\Backend;

use Entity\Settings;

class None extends BackendAbstract
{
    protected $supports_media = false;
    protected $supports_requests = false;

    public function read()
    {}

    public function write()
    {}

    public function isRunning()
    {
        return true;
    }

    public function start()
    {
        $this->log(_('AutoDJ is currently disabled. Enable it from the station profile.'));
    }
}