<?php
namespace Modules\Frontend\Controllers;

use Entity\Settings;

class SetupController extends BaseController
{
    public function init()
    {
        if (Settings::getSetting('setup_complete', 0) != 0)
            return $this->redirectToRoute([]);

        return NULL;
    }
    
    /**
     * Setup Step 1:
     * Create Super Administrator Account
     */
    public function registerAction()
    {
        // Check for user accounts.
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')
            ->getSingleScalarResult();

        if ($num_users != 0)
            return $this->redirectFromHere(['action' => 'index']);

        // Create first account form.
        $this->view->setTemplateAfter('minimal');

        $form = new \App\Form($this->current_module_config->forms->register);
        
        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            // Create actions and roles supporting Super Admninistrator.
            $action = new \Entity\Action;
            $action->name = 'administer all';
            $this->em->persist($action);

            $role = new \Entity\Role;
            $role->name = 'Super Administrator';
            $role->actions->add($action);
            $this->em->persist($role);

            // Create user account.
            $user = new \Entity\User;
            $user->email = $data['email'];
            $user->setAuthPassword($data['password']);
            $user->roles->add($role);
            $this->em->persist($user);

            // Write to DB.
            $this->em->flush();

            $login_credentials = array(
                'username'  => $data['email'],
                'password'  => $data['auth_password'],
            );
            $login_success = $this->auth->authenticate($login_credentials);

            return $this->redirectFromHere(['action' => 'index']);
        }
    }

    /**
     * Setup Step 2:
     * Create Station and Parse Metadata
     */
    public function indexAction()
    {
        // Check for user accounts.
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')
            ->getSingleScalarResult();

        if ($num_users == 0)
            return $this->redirectFromHere(['action' => 'register']);

        // New station setup form.
        $this->flash('<b>Test!</b><br>Test');
        $this->flash('<b>Test!</b><br>This is a longer message. Success message. Thingy success. Yay.', 'green');
        $this->flash('<b>Test!</b><br>An error has occurred! This is an error message!', 'red');

    }
}
