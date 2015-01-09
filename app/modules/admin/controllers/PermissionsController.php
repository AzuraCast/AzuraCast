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
        
        if ($this->_hasParam('id'))
        {
            $record = Action::find($this->_getParam('id'));
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
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
            return;
        }

        $this->view->setVar('title', 'Add/Edit Action');
        $this->renderForm($form);
    }
    
    public function deleteactionAction()
    {
        $this->validateToken($this->_getParam('csrf'));
        
        $action = Action::find($this->_getParam('id'));
        if ($action)
            $action->delete();
            
        $this->alert('Action deleted!', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }

    public function editroleAction()
    {
        $form_config = $this->current_module_config->forms->role->form->toArray();
        
        $form = new \DF\Form($form_config);
        
        if ($this->_hasParam('id'))
        {
            $record = Role::find($this->_getParam('id'));
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
        $this->validateToken($this->_getParam('csrf'));
        
        $record = Role::find($this->_getParam('id'));
        if ($record instanceof Role)
            $record->delete();
        
        $this->alert('<b>Role deleted!</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}