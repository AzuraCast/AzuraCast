<?php
namespace Controller\Admin;

use App\Acl;
use App\Http\Request;
use App\Http\Response;
use AzuraCast\Sync;
use Entity;

class IndexController
{
    /** @var Acl */
    protected $acl;

    /** @var Sync */
    protected $sync;

    /**
     * IndexController constructor.
     * @param Acl $acl
     * @param Sync $sync
     */
    public function __construct(Acl $acl, Sync $sync)
    {
        $this->acl = $acl;
        $this->sync = $sync;
    }

    /**
     * Main display.
     */
    public function indexAction(Request $request, Response $response): Response
    {
        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        // Remove the sidebar on the homepage.
        $view->sidebar = null;

        // Synchronization statuses
        if ($this->acl->userAllowed($user, 'administer all')) {
            $view->sync_times = $this->sync->getSyncTimes();
        }

        return $view->renderToResponse($response, 'admin/index/index');
    }

    public function syncAction(Request $request, Response $response, $type): Response
    {
        switch ($type) {
            case "long":
                $this->sync->syncLong();
                break;

            case "medium":
                $this->sync->syncMedium();
                break;

            case "short":
                $this->sync->syncShort();
                break;

            case "nowplaying":
            default:
                $segment = $request->getParam('segment', 1);
                define('NOWPLAYING_SEGMENT', $segment);

                $this->sync->syncNowplaying(true);
                break;
        }

        return $response->write('Sync task complete. See log above.');
    }
}