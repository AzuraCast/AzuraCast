<?php
namespace Modules\Frontend\Controllers;

use \App\Debug;
use \App\Utilities;
use Entity\StationPlaylist;

class UtilController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        Debug::setEchoMode();

        // -------- START HERE -------- //

        $station = \Entity\Station::find(1);
        $fe = $station->getFrontendAdapter();
        $fe->read();

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}