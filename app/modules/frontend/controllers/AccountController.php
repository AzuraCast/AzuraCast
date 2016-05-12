<?php
namespace Modules\Frontend\Controllers;

use \Entity\User;
use \Entity\UserExternal;

class AccountController extends BaseController
{
    public function init()
    {
        return null;
    }

    public function indexAction()
    {
        if ($this->auth->isLoggedIn())
            return $this->redirectHome();
        else
            return $this->redirectFromHere(array('action' => 'login'));
    }

    public function loginAction()
    {
        if (!$_POST)
            $this->storeReferrer('login', false);

        $this->view->setTemplateAfter('minimal');

        $form = new \App\Form($this->current_module_config->forms->login);

        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $login_success = $this->auth->authenticate($form->getValues());

            if($login_success)
            {
                $user = $this->auth->getLoggedInUser();

                $this->alert('<b>Logged in successfully.</b><br>User: '.$user->email, 'green');

                $url = $this->di->get('url');

                if ($this->acl->isAllowed('view administration'))
                    $default_url = $url->route(array('module' => 'admin'));
                else
                    $default_url = $url->route(array('module' => 'default'));

                return $this->redirectToStoredReferrer('login', $default_url);
            }

            return $this->redirectFromHere(['action' => 'index']);
        }

        $this->view->form = $form;
    }

    public function logoutAction()
    {
        $this->auth->logout();

        $session = $this->di->get('session');
        $session->destroy();

        $this->redirectToRoute(array('module' => 'default'));
    }

    /*
    public function forgotAction()
    {
        $form = new \App\Form($this->current_module_config->forms->forgot);

        if ($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $user = User::getRepository()->findOneBy(array('email' => $data['contact_email']));
            if ($user instanceof User)
            {
                $user->generateAuthRecoveryCode();
                $user->save();

                \App\Messenger::send(array(
                    'to'        => $user->email,
                    'subject'   => 'Password Recovery Code',
                    'template'  => 'forgotpw',
                    'vars'      => array(
                        'record' => $user,
                    ),
                ));
            }

            $this->alert('<b>A password recovery link has been sent to your e-mail address.</b><br>Click the link in the e-mail to reset your password.', 'green');
            $this->redirectHome();
            return;
        }

        $this->view->setVar('title', 'Forgot My Password');
        $this->renderForm($form);
    }

    public function recoverAction()
    {
        $id = (int)$this->getParam('id');
        $code = $this->getParam('code');

        $user = User::getRepository()->findOneBy(array('id' => $id, 'auth_recovery_code' => $code));

        if (!($user instanceof User))
            throw new \App\Exception\DisplayOnly('Invalid ID or recovery code provided!');

        $temp_pw = substr(sha1(mt_rand()), 0, 8);

        $user->setAuthPassword($temp_pw);
        $user->auth_recovery_code = '';
        $user->save();

        $this->auth->authenticate(array('username' => $user->email, 'password' => $temp_pw));

        $this->alert('<b>Logged in successfully.</b><br>Your account password has been reset. Please change your password using the form below.', 'green');
        $this->redirectToRoute(array('controller' => 'profile', 'action' => 'edit'));
        return;
    }
    */
}
