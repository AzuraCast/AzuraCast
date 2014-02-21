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

    public function addAction()
    {
		$form = new \DF\Form($this->current_module_config->forms->user_new->form);

        if( !empty($_POST) && $form->isValid($_POST) )
        {
			$data = $form->getValues();
			$uins_raw = explode("\n", $data['uin']);
			
			foreach((array)$uins_raw as $uin)
			{
				$uin = trim($uin);

				if (strlen($uin) == 9)
				{
					$user = User::getOrCreate($uin);
					$user->fromArray(array('roles' => $data['roles']));
					$this->em->persist($user);

					$this->alert('User <a href="'.\DF\Url::route(array('module' => 'admin', 'controller' => 'users', 'action' => 'edit', 'id' => $user->id)).'" title="Edit User">'.$user->lastname.', '.$user->firstname.'</a> successfully updated/added.');
				}
			}

			$this->em->flush();
			
			$this->redirectToRoute(array('module' => 'admin', 'controller' => 'users'));
			return;
        }

        $this->view->headTitle('Add User');
        $this->renderForm($form);
    }
    
    public function editAction()
    {
		// Handle UIN translation.
		if ($this->_hasParam('uin'))
		{
			$user = User::getRepository()->findOneByUin($this->_getParam('uin'));
			
			if ($user instanceof User)
				$this->redirectFromHere(array('uin' => NULL, 'id' => $user->id));
			else
				throw new \DF\Exception\DisplayOnly('User not found!');
			return;
		}
		
        $id = (int)$this->_getParam('id');
        $user = User::find($id);
		
		$form = new \DF\Form($this->current_module_config->forms->user_edit->form);
		
		if (!($user instanceof User))
			throw new \DF\Exception\DisplayOnly('User not found!');

		$form->setDefaults($user->toArray(TRUE, TRUE));
	
		if( !empty($_POST) && $form->isValid($_POST) )
		{
			$data = $form->getValues();

			$user->fromArray($data);
			$user->save();

			$this->alert('<b>User updated!</b>', 'green');
			$this->redirectToRoute(array('module'=>'admin','controller'=>'users'));
			return;
		}

		$this->view->headTitle('Edit User');
		$this->renderForm($form);
    }

    public function deleteAction()
    {
    	$id = (int)$this->_getParam('id');
        $user = User::find($id);

        $user->flag_delete = !($user->flag_delete);
        $user->save();

        $this->alert('<b>User deleted status toggled.</b>', 'green');
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