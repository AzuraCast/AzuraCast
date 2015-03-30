<?php
namespace Modules\Frontend\Controllers;

use \Entity\Song;

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

        \PVL\Debug::setEchoMode();

        \PVL\NotificationManager::run();

        \PVL\Debug::log('Donezo!');
    }
}