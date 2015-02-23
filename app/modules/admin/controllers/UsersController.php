<?php
namespace Modules\Admin\Controllers;

use \Entity\User;

class UsersController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        if ($_GET)
            $this->redirectFromHere($_GET);
        
        if ($this->hasParam('q'))
        {
            $this->view->q = $q = trim($this->getParam('q'));

            $query = $this->em->createQuery('SELECT u, r FROM Entity\User u LEFT JOIN u.roles r WHERE (u.name LIKE :query OR u.email LIKE :query) ORDER BY u.name ASC')
                ->setParameter('query', '%'.$q.'%');
        }
        else
        {
            $query = $this->em->createQuery('SELECT u, r FROM Entity\User u LEFT JOIN u.roles r ORDER BY u.name ASC');
        }
        
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1), 50);
    }

    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->user_edit->form);
        
        if ($this->hasParam('id'))
        {
            $record = User::find($this->getParam('id'));
            $record_defaults = $record->toArray(TRUE, TRUE);

            unset($record_defaults['auth_password']);

            $form->setDefaults($record_defaults);
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            
            if (!($record instanceof User))
                $record = new User;
            
            $record->fromArray($data);
            $record->save();
            
            $this->alert('User updated.', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
            return;
        }

        $this->renderForm($form, 'edit', 'Edit Record');
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');
        $user = User::find($id);

        if ($user instanceof User)
            $user->delete();

        $this->alert('<b>User deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function impersonateAction()
    {
        $id = (int)$this->getParam('id');
        $user = User::find($id);

        if (!($user instanceof User))
            throw new \DF\Exception\DisplayOnly('User not found!');
        
        // Set new identity in Zend_Auth
        $this->auth->masqueradeAsUser($user);

        $this->alert('<b>Logged in as '.$user->firstname.' '.$user->lastname.'.</b>', 'green');
        $this->redirectHome();
        return;
    }
}