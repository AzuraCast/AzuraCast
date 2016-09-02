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
        $frontend = $this->station->getFrontendAdapter();
        $backend = $this->station->getBackendAdapter();

        $backend->stop();
        $frontend->stop();

        $frontend->write();
        $backend->write();

        $this->view->frontend_result = $frontend->start();
        $this->view->backend_result = $backend->start();
    }
}