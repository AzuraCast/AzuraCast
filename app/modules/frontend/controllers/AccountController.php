<?php
namespace Modules\Frontend\Controllers;

use \Entity\User;
use \Entity\UserExternal;

class AccountController extends BaseController
{
    public function indexAction()
    {
        if ($this->auth->isLoggedIn())
            $this->redirectToRoute(array('module' => 'default', 'controller' => 'profile'));
        else
            $this->redirectFromHere(array('action' => 'login'));
    }

    public function profileAction()
    {
        $this->redirectToRoute(array('controller' => 'profile'));
        return;
    }
    
    public function registerAction()
    {
        if (!$_POST)
        {
            $this->storeReferrer('login');
            $this->forceSecure();
        }

        $form = new \DF\Form($this->current_module_config->forms->register);
        
        if ($_POST)
        {
            if ($form->isValid($_POST))
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

                    $default_url = \DF\Url::route(array('module' => 'default'));
                    $this->redirectToStoredReferrer('login', $default_url);
                    return;
                }
            }
        }

        $this->view->title = 'Create New Account';
        $this->renderForm($form);
    }

    public function loginAction()
    {
        if (!$_POST)
        {
            $this->storeReferrer('login', false);
            $this->forceSecure();
        }

        $form = new \DF\Form($this->current_module_config->forms->login);

        if ($this->hasParam('provider'))
        {
            $provider_name = $this->getParam('provider');
 
            try
            {
                $ha_config = $this->_getHybridConfig();
                $hybridauth = new \Hybrid_Auth($ha_config);
     
                // try to authenticate with the selected provider
                $adapter = $hybridauth->authenticate($provider_name);

                if ($hybridauth->isConnectedWith($provider_name))
                {
                    $user_profile = $adapter->getUserProfile();

                    $user = UserExternal::processExternal($provider_name, $user_profile);
                    $this->auth->setUser($user);
                }
            }
            catch(\Exception $e)
            {
                if ($e instanceof \PVL\Exception\AccountNotLinked)
                    $this->alert('<b>Your social network account is not linked to a PVL account yet!</b><br>Sign in below, or create a new PVL account, then link your social accounts from your profile.', 'red');
                else
                    $this->alert($e->getMessage(), 'red');
            }
        }
        else if ($_POST)
        {
            if ($form->isValid($_POST))
            {
                $login_success = $this->auth->authenticate($form->getValues());
                
                if($login_success)
                {
                    $user = $this->auth->getLoggedInUser();
                    
                    $this->alert('<b>Logged in successfully. Welcome back, '.$user->name.'!</b><br>For security purposes, log off when your session is complete.', 'green');

                    if ($this->acl->isAllowed('view administration'))
                        $default_url = \DF\Url::route(array('module' => 'admin'));
                    else
                        $default_url = \DF\Url::route(array('module' => 'default'));

                    $this->redirectToStoredReferrer('login', $default_url);
                    return;
                }
            }
        }

        // Auto-bounce back if logged in.
        if ($this->auth->isLoggedIn())
            $this->redirectToStoredReferrer('login', \DF\Url::route());

        $this->view->external_providers = UserExternal::getExternalProviders();
        $this->view->form = $form;
    }

    public function linkAction()
    {
        $this->acl->checkPermission('is logged in');
        $this->doNotRender();

        // Link external account.
        $user = $this->auth->getLoggedInUser();

        $provider_name = $this->getParam('provider');

        $ha_config = $this->_getHybridConfig();
        $hybridauth = new \Hybrid_Auth($ha_config);

        // try to authenticate with the selected provider
        $adapter = $hybridauth->authenticate($provider_name);

        if ($hybridauth->isConnectedWith($provider_name))
        {
            $user_profile = $adapter->getUserProfile();
            UserExternal::processExternal($provider_name, $user_profile, $user);

            $this->alert('<b>Account successfully linked!</b>', 'green');

            $this->redirectToRoute(array('module' => 'default', 'controller' => 'profile'));
            return;
        }
    }

    public function unlinkAction()
    {
        $this->acl->checkPermission('is logged in');
        $this->doNotRender();

        // Unlink external account.
        $user = $this->auth->getLoggedInUser();

        $provider_name = $this->getParam('provider');

        foreach($user->external_accounts as $acct)
        {
            if ($acct->provider == $provider_name)
                $acct->delete();
        }

        $this->alert('<b>Account successfully unlinked!</b>', 'green');

        $this->redirectToRoute(array('module' => 'default', 'controller' => 'profile'));
        return;
    }

    public function hybridAction()
    {
        $ha_config = $this->_getHybridConfig();

        \Hybrid_Auth::initialize($ha_config);
        \Hybrid_Endpoint::process();
    }

    public function forgotAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->forgot);

        if ($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $user = User::getRepository()->findOneBy(array('email' => $data['contact_email']));
            if ($user instanceof User)
            {
                $user->generateAuthRecoveryCode();
                $user->save();

                \DF\Messenger::send(array(
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
        $id = (int)$this->_getParam('id');
        $code = $this->_getParam('code');

        $user = User::getRepository()->findOneBy(array('id' => $id, 'auth_recovery_code' => $code));

        if (!($user instanceof User))
            throw new \DF\Exception\DisplayOnly('Invalid ID or recovery code provided!');

        $temp_pw = substr(sha1(mt_rand()), 0, 8);

        $user->setAuthPassword($temp_pw);
        $user->auth_recovery_code = '';
        $user->save();

        $this->auth->authenticate(array('username' => $user->email, 'password' => $temp_pw));

        $this->alert('<b>Logged in successfully.</b><br>Your account password has been reset. Please change your password using the form below.', 'green');
        $this->redirectToRoute(array('controller' => 'account', 'action' => 'editprofile'));
        return;
    }

    public function logoutAction()
    {
        $this->auth->logout();
        $this->redirectToRoute(array('module' => 'default'));
    }

    public function endimpersonateAction()
    {
        $this->auth->endMasquerade();

        $this->alert('<b>Switched back to main account successfully.</b>', 'green');
        $this->redirectHome();
    }

    public function removeAction()
    {
        $this->acl->checkPermission('is logged in');

        if ($_POST['confirm'] == 'confirm')
        {
            $user = $this->auth->getLoggedInUser();
            $this->auth->logout();

            // Parting is such sweet sorrow...
            $user->delete();

            $this->alert('<b>Account successfully deleted.</b><br>You can recreate your PVL account at any time by registering again.', 'green');
            $this->redirectHome();
            return;
        }
    }

    protected function _getHybridConfig()
    {
        // Force "scheme" injection for base URLs.
        $ha_config = $this->config->apis->hybrid_auth->toArray();
        $ha_config['base_url'] = \DF\Url::addSchemePrefix(\DF\Url::routeFromHere(array('action' => 'hybrid')));

        return $ha_config;
    }
}
