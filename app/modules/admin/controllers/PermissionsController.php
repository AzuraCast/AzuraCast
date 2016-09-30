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
        $this->view->actions = $this->em->getRepository(Action::class)->fetchArray(false, 'name');
        $this->view->roles = $this->em->getRepository(Role::class)->fetchArray(false, 'name');
    }
    
    public function editactionAction()
    {
        $form = new \App\Form($this->current_module_config->forms->action->form);
        
        if ($this->hasParam('id'))
        {
            $record = $this->em->getRepository(Action::class)->find($this->getParam('id'));
            $form->setDefaults($record->toArray($this->em));
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            
            if (!($record instanceof Action))
                $record = new Action;
            
            $record->fromArray($this->em, $data);

            $this->em->persist($record);
            $this->em->flush();
            
            $this->alert(_('Action updated.'), 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
        }

        return $this->renderForm($form, 'edit', _('Edit Action'));
    }
    
    public function deleteactionAction()
    {
        $action = $this->em->getRepository(Action::class)->find($this->getParam('id'));
        if ($action)
            $action->delete();
            
        $this->alert(_('Action deleted.'), 'green');
        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
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
        $form_config['elements']['actions'][1]['options'] = $this->em->getRepository(Action::class)->fetchSelect();

        $form = new \App\Form($form_config);
        
        if ($this->hasParam('id'))
        {
            $record = $this->em->getRepository(Role::class)->find($this->getParam('id'));
            $form->setDefaults($record->toArray($this->em, TRUE, TRUE));
        }

        if( !empty($_POST) && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            
            if (!($record instanceof Role))
                $record = new Role;

            $record->fromArray($this->em, $data);

            $this->em->persist($record);
            $this->em->flush();

            $this->alert('<b>'._('Role updated.').'</b>', 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
        }

        return $this->renderForm($form, 'edit', _('Edit Role'));
    }

    public function deleteroleAction()
    {
        $record = $this->em->getRepository(Role::class)->find($this->getParam('id'));
        if ($record instanceof Role)
            $this->em->remove($record);

        $this->em->flush();
        
        $this->alert('<b>'._('Role deleted!').'</b>', 'green');
        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}