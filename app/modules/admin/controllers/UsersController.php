<?php
namespace Modules\Admin\Controllers;

use Entity\Role;
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

        $this->view->pager = new \App\Paginator\Doctrine($query, $this->getParam('page', 1), 50);
    }

    public function editAction()
    {
        $form_config = $this->current_module_config->forms->user->form->toArray();
        $form_config['elements']['roles'][1]['options'] = $this->em->getRepository(Role::class)->fetchSelect();

        $form = new \App\Form($form_config);
        
        if ($this->hasParam('id'))
        {
            $record = $this->em->getRepository(User::class)->find($this->getParam('id'));
            $record_defaults = $record->toArray($this->em, TRUE, TRUE);

            unset($record_defaults['auth_password']);

            $form->setDefaults($record_defaults);
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            
            if (!($record instanceof User))
                $record = new User;
            
            $record->fromArray($this->em, $data);

            $this->em->persist($record);
            $this->em->flush();
            
            $this->alert(_('Record updated.'), 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
        }

        return $this->renderForm($form, 'edit', _('Edit Record'));
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');
        $user = $this->em->getRepository(User::class)->find($id);

        if ($user instanceof User)
            $this->em->remove($user);

        $this->em->flush();

        $this->alert('<b>'._('User deleted.').'</b>', 'green');
        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function impersonateAction()
    {
        $id = (int)$this->getParam('id');
        $user = User::find($id);

        if (!($user instanceof User))
            throw new \App\Exception(_('User not found!'));
        
        // Set new identity in Zend_Auth
        $this->auth->masqueradeAsUser($user);

        $this->alert('<b>'.sprintf(_('Logged in as %s.'), $user->firstname.' '.$user->lastname).'</b>', 'green');
        return $this->redirectHome();
    }
}