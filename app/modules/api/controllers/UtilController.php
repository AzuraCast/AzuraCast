<?php
namespace Modules\Api\Controllers;

use \App\Debug;
use \App\Utilities;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        Debug::setEchoMode();
        Debug::log('Donezo!');
    }
}