<?php
namespace Modules\Stations\Controllers;

use Entity\Station;

class UtilController extends BaseController
{
    /**
     * Write configuration changes to the station backend and restart it.
     */
    public function writeAction()
    {
        $backend = $this->station->getBackendAdapter();

        $backend->write();
        $this->view->backend_result = $backend->restart();
    }

    /**
     * Restart all services associated with the radio.
     */
    public function restartAction()
    {
        $backend = $this->station->getBackendAdapter();
        $this->view->backend_result = $backend->restart();

        $frontend = $this->station->getFrontendAdapter();
        $this->view->frontend_result = $frontend->restart();
    }
}