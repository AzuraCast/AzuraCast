<?php
use \Entity\User;

class Admin_UsersController extends \DF\Controller\Action
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        if ($_GET)
            $this->redirectFromHere($_GET);
        
        if ($this->_hasParam('q'))
        {
            $this->view->q = $q = trim($this->_getParam('q'));

            $query = $this->em->createQuery('SELECT u FROM Entity\User u WHERE (u.name LIKE :q OR u.email LIKE :q) ORDER BY u.name ASC')
                ->setParameter('q', '%'.$q.'%');
        }
        else
        {
            $query = $this->em->createQuery('SELECT u FROM Entity\User u ORDER BY u.name ASC');
        }
        
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->_getParam('page', 1), 50);
    }

    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->user_edit->form);
        
        if ($this->_hasParam('id'))
        {
            $record = User::find($this->_getParam('id'));
            $form->setDefaults($record->toArray());
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

        $this->view->headTitle('Add/Edit User');
        $this->renderForm($form);
    }

    public function deleteAction()
    {
        $id = (int)$this->_getParam('id');
        $user = User::find($id);

        if ($user instanceof User)
            $user->delete();

        $this->alert('<b>User deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function impersonateAction()
    {
        $id = (int)$this->_getParam('id');
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