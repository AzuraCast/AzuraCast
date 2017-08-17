<?php
namespace Controller\Frontend;

use Entity\Settings;

class AccountController extends BaseController
{
    public function init()
    {
        if ($this->em->getRepository(Settings::class)->getSetting('setup_complete', 0) == 0) {
            $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();

            if ($num_users == 0) {
                return $this->redirectToRoute(['module' => 'frontend', 'controller' => 'setup']);
            }
        }

        return null;
    }

    public function indexAction()
    {
        if ($this->auth->isLoggedIn()) {
            return $this->redirectHome();
        } else {
            return $this->redirectFromHere(['action' => 'login']);
        }
    }

    public function loginAction()
    {
        if ($this->auth->isLoggedIn()) {
            return $this->redirectHome();
        }

        if (!$_POST) {
            $this->storeReferrer('login', false);
        }

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $login_success = $this->auth->authenticate($_POST['username'], $_POST['password']);

            if ($login_success) {
                $this->acl->reload();

                $user = $this->auth->getLoggedInUser();

                $this->alert('<b>' . _('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $default_url = $this->url->named('home');

                return $this->redirectToStoredReferrer('login', $default_url);
            } else {
                $this->alert('<b>' . _('Login unsuccessful') . '</b><br>' . _('Your credentials could not be verified.'),
                    'red');

                return $this->redirectHere();
            }
        }
    }

    public function logoutAction()
    {
        $this->auth->logout();

        $session = $this->di->get('session');
        $session->destroy();

        return $this->redirectToName('account:login');
    }

    public function endmasqueradeAction()
    {
        $this->doNotRender();

        $this->auth->endMasquerade();

        return $this->redirectToRoute(['module' => 'admin', 'controller' => 'users']);
    }

    /*
    public function forgotAction()
    {
        $form = new \App\Form($this->current_module_config->forms->forgot);

        if ($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $user = $this->em->getRepository(User::class)->findOneBy(array('email' => $data['contact_email']));
            if ($user instanceof User)
            {
                $user->generateAuthRecoveryCode();
                $this->em->persist($user);
                $this->em->flush();

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
            throw new \App\Exception('Invalid ID or recovery code provided!');

        $temp_pw = substr(sha1(mt_rand()), 0, 8);

        $user->setAuthPassword($temp_pw);
        $user->auth_recovery_code = '';

        $this->em->persist($user);
        $this->em->flush();

        $this->auth->authenticate(array('username' => $user->email, 'password' => $temp_pw));

        $this->alert('<b>Logged in successfully.</b><br>Your account password has been reset. Please change your password using the form below.', 'green');
        $this->redirectToRoute(array('controller' => 'profile', 'action' => 'edit'));
        return;
    }
    */
}
