<?php
namespace Modules\Frontend\Controllers;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();


        \PVL\Debug::setEchoMode();

        \PVL\NowPlaying::generate();
    }
}