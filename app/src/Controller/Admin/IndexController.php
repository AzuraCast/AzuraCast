<?php
namespace Controller\Admin;

use App\Http\Request;
use App\Http\Response;

class IndexController extends BaseController
{
    /**
     * Main display.
     */
    public function indexAction(Request $request, Response $response): Response
    {
        // Remove the sidebar on the homepage.
        $this->view->sidebar = null;

        // Synchronization statuses
        if ($this->acl->isAllowed('administer all')) {
            /** @var \AzuraCast\Sync $sync */
            $sync = $this->di[\AzuraCast\Sync::class];
            $this->view->sync_times = $sync->getSyncTimes();
        }

        return $this->render($response, 'admin/index/index');
    }

    public function syncAction(Request $request, Response $response, $args): Response
    {
        $this->acl->checkPermission('administer all');

        ob_start();

        \App\Debug::setEchoMode(true);
        \App\Debug::startTimer('sync_task');

        $type = $args['type'];

        /** @var \AzuraCast\Sync $sync */
        $sync = $this->di[\AzuraCast\Sync::class];

        switch ($type) {
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
                $segment = $request->getParam('segment', 1);
                define('NOWPLAYING_SEGMENT', $segment);

                $sync->syncNowplaying(true);
                break;
        }

        \App\Debug::endTimer('sync_task');
        \App\Debug::log('Sync task complete. See log above.');

        $result = ob_get_clean();

        return $response->write($result);
    }
}