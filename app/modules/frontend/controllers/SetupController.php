<?php
namespace Modules\Frontend\Controllers;

use Entity\Settings;
use Entity\User;

class SetupController extends BaseController
{
    public function init()
    {
        if (Settings::getSetting('setup_complete', 0) != 0)
            return $this->redirectToRoute([]);

        return NULL;
    }
    
    public function indexAction()
    {
        // Check for user accounts.
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')
            ->getSingleScalarResult();

        if ($num_users == 0)
            return $this->redirectFromHere(['action' => 'register']);

        // New station setup form.

    }

    public function registerAction()
    {
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')
            ->getSingleScalarResult();

        if ($num_users != 0)
            return $this->redirectFromHere(['action' => 'index']);

        // Create first account form.
        $this->view->setLayout('minimal');

        $form = new \App\Form($this->current_module_config->forms->register);
        
        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $existing_user = User::getRepository()->findOneBy(array('email' => $data['email']));

            if ($existing_user instanceof User)
            {
                $this->alert('A user with that e-mail address already exists!', 'red');
            }
            else
            {
                $new_user = new User;
                $new_user->fromArray($data);
                $new_user->save();

                $login_credentials = array(
                    'username'  => $data['email'],
                    'password'  => $data['auth_password'],
                );
                $login_success = $this->auth->authenticate($login_credentials);

                $this->alert('<b>Your account has been successfully created.</b><br>You have been automatically logged in to your new account.', 'green');

                $default_url = \App\Url::route(array('module' => 'default'));
                $this->redirectToStoredReferrer('login', $default_url);
                return;
            }
        }
    }
}
