<?php
namespace Modules\Frontend\Controllers;

use \Entity\Song;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        \PVL\Debug::setEchoMode();

        \PVL\VideoManager::generate();
    }
}