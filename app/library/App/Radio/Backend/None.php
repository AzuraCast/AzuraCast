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

    public function stop()
    {}

    public function start()
    {
        $this->log(_('AutoDJ is currently disabled. Enable it from the station profile.'));
    }

    public function restart()
    {
        $this->stop();
        $this->start();
    }

    public function request($music_file)
    {
        return $this->command('requests.push '.$music_file);
    }

    public function skip()
    { }

    public function command($command_str)
    { }

    public function getStreamerInfo()
    {
        return [
            'host'          => $this->di['em']->getRepository('Entity\Settings')->getSetting('base_url', 'localhost'),
            'icecast_port'  => $this->_getHarborPort(),
            'shoutcast_port' => $this->_getHarborPort()+1,
        ];
    }

    protected function _getHarborPort()
    {
        return (8000 + (($this->station->id - 1) * 10) + 5);
    }

    protected function _getTelnetPort()
    {
        return (8500 + (($this->station->id - 1) * 10));
    }
}