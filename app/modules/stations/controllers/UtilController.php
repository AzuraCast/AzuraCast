<?php
namespace Modules\Stations\Controllers;

use Entity\Station;

class UtilController extends BaseController
{
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