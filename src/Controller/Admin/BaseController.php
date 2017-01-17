<?php
namespace Controller\Admin;

class BaseController extends \AzuraCast\Mvc\Controller
{
    protected function preDispatch()
    {
        parent::preDispatch();

        // Load dashboard.
        $panels = $this->config->admin->dashboard->toArray();

        foreach($panels as $sidebar_category => &$sidebar_info)
        {
            foreach($sidebar_info['items'] as $item_name => $item_params)
            {
                $permission = $item_params['permission'];
                if (!is_bool($permission))
                    $permission = $this->di['acl']->isAllowed($permission);

                if (!$permission)
                    unset($sidebar_info['items'][$item_name]);
            }

            if (empty($sidebar_info['items']))
                unset($panels[$sidebar_category]);
        }

        $this->view->admin_panels = $panels;

        if (!($this->controller == 'index' && $this->action == 'index'))
            $this->view->sidebar = $this->view->fetch('common::sidebar');

        return true;
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('view administration');
    }
}