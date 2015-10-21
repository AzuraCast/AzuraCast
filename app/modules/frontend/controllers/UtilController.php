<?php
namespace Modules\Frontend\Controllers;

use \PVL\Debug;
use \PVL\Utilities;

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

        \PVL\CentovaCast::sync();
        Debug::log('CCast Sync Complete');

        $station = \Entity\Station::getRepository()->findOneBy(array('name' => 'PonyvilleFM'));
        $tracks = \PVL\CentovaCast::fetchTracks($station);
        Debug::print_r($tracks);

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}