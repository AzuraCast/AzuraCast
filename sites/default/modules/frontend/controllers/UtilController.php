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

        $station_managers = \Entity\StationManager::fetchAll();
        $records = 0;

        foreach($station_managers as $sm_row)
        {
            $user = \Entity\User::getOrCreate($sm_row->email);

            $user->stations->add($sm_row->station);
            $user->save();

            $records++;
        }

        Debug::log('Processed '.$records.' records.');

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}