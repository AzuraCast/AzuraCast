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

        $raw = \PVL\NewsAdapter\Tumblr::fetch('http://news.ponyvillelive.com/rss');
        Debug::print_r($raw);

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}