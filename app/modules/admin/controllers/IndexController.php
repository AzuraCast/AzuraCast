<?php
namespace Modules\Admin\Controllers;

class IndexController extends BaseController
{
    /**
     * Main display.
     */
    public function indexAction()
    {
        // Load dashboard.
        $panels = $this->current_module_config->dashboard->toArray();

        foreach($panels as $sidebar_category => &$sidebar_items)
        {
            foreach($sidebar_items as $item_name => $item_params)
            {
                $permission = $item_params['permission'];
                if (!is_bool($permission))
                    $permission = $this->di['acl']->isAllowed($permission);

                if (!$permission)
                    unset($sidebar_items[$item_name]);
            }

            if (empty($sidebar_items))
                unset($panels[$sidebar_category]);
        }

        $this->view->panels = $panels;

        // Synchronization statuses
        if ($this->acl->isAllowed('administer all'))
        {
            /** @var \App\Sync $sync */
            $sync = $this->di['sync'];
            $this->view->sync_times = $sync->getSyncTimes();
        }
    }

    public function syncAction()
    {
        $this->acl->checkPermission('administer all');

        $this->doNotRender();

        \App\Debug::setEchoMode(TRUE);
        \App\Debug::startTimer('sync_task');

        $type = $this->getParam('type', 'nowplaying');

        /** @var \App\Sync $sync */
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
    }
}