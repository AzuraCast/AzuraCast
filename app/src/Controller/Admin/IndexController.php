<?php
namespace Controller\Admin;

use App\Acl;
use App\Http\Request;
use App\Http\Response;
use AzuraCast\Sync\Runner;
use Entity;

class IndexController
{
    /** @var Acl */
    protected $acl;

    /** @var Runner */
    protected $sync;

    /**
     * IndexController constructor.
     * @param Acl $acl
     * @param Runner $sync
     */
    public function __construct(Acl $acl, Runner $sync)
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
                $this->sync->syncLong(true);
                break;

            case "medium":
                $this->sync->syncMedium(true);
                break;

            case "short":
                $this->sync->syncShort(true);
                break;

            case "nowplaying":
            default:
                $this->sync->syncNowplaying(true);
                break;
        }

        return $response->write('Sync task complete. See log above.');
    }
}