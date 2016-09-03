<?php
namespace Modules\Stations\Controllers;

use Entity\Settings;
use Entity\Station;

class ControlController extends BaseController
{
    public function indexAction()
    {
        $ba = $this->station->getBackendAdapter();

        $this->view->backend_type = $this->station->backend_type;
        $this->view->backend_config = (array)$this->station->backend_config;
        $this->view->backend_is_running = $ba->isRunning();

        $fa = $this->station->getFrontendAdapter();

        $this->view->base_url = Settings::getSetting('base_url', 'localhost');
        $this->view->frontend_type = $this->station->frontend_type;
        $this->view->frontend_config = (array)$this->station->frontend_config;
        $this->view->frontend_is_running = $fa->isRunning();
    }

    public function rebootbackendAction()
    {
        $backend = $this->station->getBackendAdapter();

        $backend->stop();
        $backend->write();
        $backend->start();

        $this->alert('<b>Backend rebooted.</b>', 'green');
        return $this->redirectFromHere(['action' => 'index']);
    }

    public function backendskipAction()
    {
        $ba = $this->station->getBackendAdapter();
        $ba->skip();

        $this->alert('<b>Song skipped.</b>', 'green');
        return $this->redirectFromHere(['action' => 'index']);
    }

    public function rebootfrontendAction()
    {
        $frontend = $this->station->getFrontendAdapter();

        $frontend->stop();
        $frontend->write();
        $frontend->start();

        $this->alert('<b>Frontend rebooted.</b>', 'green');
        return $this->redirectFromHere(['action' => 'index']);
    }
}