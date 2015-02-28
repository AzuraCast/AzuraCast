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

        // Sync the BronyTunes library.
        \PVL\Service\BronyTunes::load(true);

        // Sync the Pony.fm library.
        \PVL\Service\PonyFm::load(true);

        // Sync the EqBeats library.
        \PVL\Service\EqBeats::load(true);

        \PVL\Debug::log('Donezo!');
    }
}