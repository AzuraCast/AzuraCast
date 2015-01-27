<?php
namespace Modules\Frontend\Controllers;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        throw new \DF\Exception('STUFF BROKE OMG');



        \PVL\Debug::setEchoMode();

        \PVL\NewsManager::syncNetwork();
    }
}