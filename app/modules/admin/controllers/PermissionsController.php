<?php
namespace Modules\Admin\Controllers;

use \Entity\Action;
use \Entity\Role;

class PermissionsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }
    
    public function indexAction()
    {
        $this->view->actions = Action::fetchArray('name');
        $this->view->roles = Role::fetchArray('name');
    }
    
    public function editactionAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->action->form);
        
        if ($this->hasParam('id'))
        {
            $record = Action::find($this->getParam('id'));
            $form->setDefaults($record->toArray());
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            
            if (!($record instanceof Action))
                $record = new Action;
            
            $record->fromArray($data);
            $record->save();
            
            $this->alert('Action updated.', 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
        }

        $this->renderForm($form, 'edit', 'Edit Record');
    }
    
    public function deleteactionAction()
    {
        $action = Action::find($this->getParam('id'));
        if ($action)
            $action->delete();
            
        $this->alert('Action deleted!', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }

    public function rolemembersAction()
    {
        $roles = $this->em->createQuery('SELECT r, a, u FROM Entity\Role r LEFT JOIN r.actions a LEFT JOIN r.users u')
            ->getArrayResult();

        $this->view->roles = $roles;
    }

    public function editroleAction()
    {
        $form_config = $this->current_module_config->forms->role->form->toArray();
        
        $form = new \DF\Form($form_config);
        
        if ($this->hasParam('id'))
        {
            $record = Role::find($this->getParam('id'));
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if( !empty($_POST) && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            
            if (!($record instanceof Role))
                $record = new Role;

            $record->fromArray($data);
            $record->save();

            $this->alert('<b>Role updated!</b>', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
            return;
        }

        $this->view->setVar('title', 'Add/Edit Role');
        $this->renderForm($form);
    }

    public function deleteroleAction()
    {
        $record = Role::find($this->getParam('id'));
        if ($record instanceof Role)
            $record->delete();
        
        $this->alert('<b>Role deleted!</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}