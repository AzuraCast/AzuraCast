<?php
namespace Modules\Admin\Controllers;

class BaseController extends \App\Mvc\Controller
{
    protected function preDispatch()
    {
        parent::preDispatch();

        // $this->forceSecure();

        return true;
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('view administration');
    }
}