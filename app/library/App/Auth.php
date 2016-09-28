<?php
namespace App;

use Entity\User;
use Entity\UserRepository;

class Auth
{
    /** @var Session */
    protected $_session;

    /** @var User|null */
    protected $_user = null;

    /** @var UserRepository */
    protected $_user_repo;

    /** @var User|null */
    protected $_masqueraded_user = null;

    public function __construct(Session $session, UserRepository $user_repo)
    {
        $this->_user_repo = $user_repo;

        $class_name = strtolower(str_replace(array('\\', '_'), array('', ''), get_called_class()));
        $this->_session = $session->get('auth_' . $class_name . '_user');
    }
    
    public function authenticate($credentials = NULL)
    {
        $user_auth = $this->_user_repo->authenticate($credentials['username'], $credentials['password']);

        if ($user_auth instanceof User)
        {
            $this->setUser($user_auth);
            return true;
        }
        else
        {
            return false;
        }
    }

    public function login()
    {
        if ($this->isLoggedIn() || php_sapi_name() == 'cli')
            return true;
        else
            return $this->authenticate();
    }

    public function logout($destination = NULL, $unset_session = true)
    {
        unset($this->_session->identity);
        unset($this->_session->user_id);
        unset($this->_session->masquerade_user_id);

        if ($unset_session)
            @session_unset();
    }

    public function isLoggedIn()
    {
        if (APP_IS_COMMAND_LINE)
            return false;

        $user = $this->getUser();
        return ($user instanceof User);
    }

    public function getLoggedInUser($real_user_only = FALSE)
    {
        if ($this->isMasqueraded() && !$real_user_only)
            return $this->getMasquerade();
        else
            return $this->getUser();
    }

    public function getUser()
    {
        if ($this->_user === NULL)
        {
            $user_id = (int)$this->_session->user_id;

            if ($user_id == 0)
            {
                $this->_user = FALSE;
                return false;
            }

            $user = $this->_user_repo->find($user_id);
            if ($user instanceof User)
            {
                $this->_user = $user;
            }
            else
            {
                unset($this->_session->user_id);
                $this->_user = FALSE;
                $this->logout();

                throw new Exception('Invalid user!');
            }
        }

        return $this->_user;
    }

    public function setUser(User $user)
    {
        // Prevent any previous identity from being used.
        unset($this->_session->identity);

        session_regenerate_id(TRUE);

        $this->_session->user_id = $user->id;

        $this->_user = $user;
        return true;
    }

    public function exists($response = null)
    {
        $user_id = (int)$this->_session->user_id;
        $user = $this->_user_repo->find($user_id);
        return ($user instanceof User);
    }

    public function getIdentity()
    {
        return $this->_session->identity;
    }
    public function setIdentity($identity)
    {
        $this->_session->identity = $identity;
    }
    public function clearIdentity()
    {
        unset($this->_session->identity);
    }

    /**
     * Masquerading
     */

    public function masqueradeAsUser($user_info)
    {
        if (!($user_info instanceof User))
            $user_info = $this->_user_repo->findOneBy(['username' => $user_info]);

        $this->_session->masquerade_user_id = $user_info->id;
        $this->_masqueraded_user = $user_info;
    }

    public function endMasquerade()
    {
        unset($this->_session->masquerade_user_id);
        $this->_masqueraded_user = null;
    }

    public function getMasquerade()
    {
        return $this->_masqueraded_user;
    }

    public function isMasqueraded()
    {
        if (!$this->isLoggedIn())
        {
            $this->_masqueraded_user = FALSE;
            return NULL;
        }

        if ($this->_masqueraded_user === NULL)
        {
            if (!$this->_session->masquerade_user_id)
            {
                $this->_masqueraded_user = FALSE;
            }
            else
            {
                $mask_user_id = (int)$this->_session->masquerade_user_id;
                if ($mask_user_id != 0)
                    $user = $this->_user_repo->find($mask_user_id);
                else
                    $user = NULL;

                if ($user instanceof User)
                {
                    $this->_masqueraded_user = $user;
                }
                else
                {
                    unset($this->_session->user_id);
                    unset($this->_session->masquerade_user_id);

                    $this->_masqueraded_user = FALSE;
                }
            }
        }

        return $this->_masqueraded_user;
    }
}