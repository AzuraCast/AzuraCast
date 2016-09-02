<?php
namespace Modules\Frontend\Controllers;

use \App\Debug;
use \App\Utilities;
use Entity\Station;
use Entity\StationPlaylist;

class UtilController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        $this->doNotRender();

        phpinfo();
    }

    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        Debug::setEchoMode();

        // -------- START HERE -------- //

        $mp3_file = '/var/azuracast/stations/best_pony_radio/media/Vinylicious.mp3';

        $station = Station::find(1);
        $ba = $station->getBackendAdapter();
        $ba->request($mp3_file);

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}