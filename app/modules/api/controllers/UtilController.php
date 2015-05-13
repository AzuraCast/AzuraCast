<?php
namespace Modules\Api\Controllers;

use \PVL\Debug;
use \PVL\Utilities;

class IndexController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        Debug::setEchoMode();

        $all_keys = \DF\Cache::getKeys();
        Debug::print_r($all_keys);

        Debug::log(\DF\Cache::getSitePrefix());

        Debug::log('Donezo!');
    }
}