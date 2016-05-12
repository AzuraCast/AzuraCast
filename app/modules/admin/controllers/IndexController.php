<?php
namespace Modules\Admin\Controllers;

class IndexController extends BaseController
{
    /**
     * Main display.
     */
    public function indexAction()
    {
        // Synchronization statuses
        if ($this->acl->isAllowed('administer all'))
            $this->view->sync_times = \App\Sync\Manager::getSyncTimes();
    }

    public function syncAction()
    {
        $this->acl->checkPermission('administer all');

        $this->doNotRender();

        \App\Debug::setEchoMode(TRUE);
        \App\Debug::startTimer('sync_task');

        $type = $this->getParam('type', 'nowplaying');
        switch($type)
        {
            case "long":
                \App\Sync\Manager::syncLong();
            break;

            case "medium":
                \App\Sync\Manager::syncMedium();
            break;

            case "short":
                \App\Sync\Manager::syncShort();
            break;

            case "nowplaying":
            default:
                $segment = $this->getParam('segment', 1);
                define('NOWPLAYING_SEGMENT', $segment);

                \App\Sync\Manager::syncNowplaying(true);
            break;
        }

        \App\Debug::endTimer('sync_task');
        \App\Debug::log('Sync task complete. See log above.');
    }
}