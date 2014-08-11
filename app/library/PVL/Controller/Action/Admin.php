<?php
namespace PVL\Controller\Action;

class Admin extends \DF\Controller\Action
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
