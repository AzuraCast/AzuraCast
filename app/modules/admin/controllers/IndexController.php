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
        {
            /** @var \AzuraCast\Sync $sync */
            $sync = $this->di['sync'];
            $this->view->sync_times = $sync->getSyncTimes();
        }
    }

    public function syncAction()
    {
        $this->acl->checkPermission('administer all');

        $this->doNotRender();

        ob_start();

        \App\Debug::setEchoMode(TRUE);
        \App\Debug::startTimer('sync_task');

        $type = $this->getParam('type', 'nowplaying');

        /** @var \AzuraCast\Sync $sync */
        $sync = $this->di['sync'];

        switch($type)
        {
            case "long":
                $sync->syncLong();
            break;

            case "medium":
                $sync->syncMedium();
            break;

            case "short":
                $sync->syncShort();
            break;

            case "nowplaying":
            default:
                $segment = $this->getParam('segment', 1);
                define('NOWPLAYING_SEGMENT', $segment);

                $sync->syncNowplaying(true);
            break;
        }

        \App\Debug::endTimer('sync_task');
        \App\Debug::log('Sync task complete. See log above.');

        $result = ob_get_clean();

        $body = $this->response->getBody();
        $body->write($result);

        return $this->response->withBody($body);
    }
}