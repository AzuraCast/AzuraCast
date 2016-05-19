<?php
namespace Modules\Frontend\Controllers;

use \App\Debug;
use \App\Utilities;
use Entity\StationPlaylist;

class UtilController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        $this->doNotRender();

        phpinfo();
    }

    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        Debug::setEchoMode();

        // -------- START HERE -------- //

        $config_path = '/etc/icecast2/icecast.xml';

        $reader = new \App\Xml\Reader();
        $data = $reader->fromFile($config_path);

        \App\Utilities::print_r($data);

        $data['mount'] = array(
            array('asdf' => array('true')),
            array('foo' => array('true')),
        );

        $writer = new \App\Xml\Writer();
        \App\Utilities::print_r($writer->toString($data, 'icecast'));

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}