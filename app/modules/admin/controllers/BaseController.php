<?php
namespace Modules\Admin\Controllers;

class BaseController extends \DF\Phalcon\Controller
{
    public function preDispatch()
    {
        parent::preDispatch();

        $this->forceSecure();
    }

    public function permissions()
    {
        return $this->acl->isAllowed('view administration');
    }
}