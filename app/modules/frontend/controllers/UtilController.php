<?php
namespace Modules\Frontend\Controllers;

use \App\Debug;
use \App\Utilities;

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

        \App\CentovaCast::sync();
        Debug::log('CCast Sync Complete');

        $station = \Entity\Station::getRepository()->findOneBy(array('name' => 'PonyvilleFM'));
        $tracks = \App\CentovaCast::fetchTracks($station);
        Debug::print_r($tracks);

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}