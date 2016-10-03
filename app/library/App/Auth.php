<?php
namespace App;

use Entity\User;
use Entity\UserRepository;

class Auth
{
    /** @var Session */
    protected $_session;

    /** @var UserRepository */
    protected $_user_repo;

    /** @var User|null */
    protected $_user = null;

    /** @var User|null */
    protected $_masqueraded_user = null;

    public function __construct(Session $session, UserRepository $user_repo)
    {
        $this->_user_repo = $user_repo;

        $class_name = strtolower(str_replace(array('\\', '_'), array('', ''), get_called_class()));
        $this->_session = $session->get('auth_' . $class_name . '_user');
    }

    /**
     * Authenticate a given username and password combination against the User repository.
     *
     * @param $username
     * @param $password
     * @return bool
     */
    public function authenticate($username, $password)
    {
        $user_auth = $this->_user_repo->authenticate($username, $password);

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

    /**
     * Log out of the currently active session.
     *
     * @param null $destination
     * @param bool $unset_session
     */
    public function logout()
    {
        unset($this->_session->user_id);
        unset($this->_session->masquerade_user_id);

        @session_unset();
    }

    /**
     * Check if a user account is currently authenticated.
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        if (APP_IS_COMMAND_LINE)
            return false;

        $user = $this->getUser();
        return ($user instanceof User);
    }

    /**
     * Get the currently logged in user.
     *
     * @param bool $real_user_only
     * @return bool|User|null|object
     */
    public function getLoggedInUser($real_user_only = FALSE)
    {
        if ($this->isMasqueraded() && !$real_user_only)
            return $this->getMasquerade();
        else
            return $this->getUser();
    }

    /**
     * Get the authenticated user entity.
     *
     * @return bool|User|null|object
     * @throws Exception
     */
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

    /**
     * Set the currently authenticated user.
     *
     * @param User $user
     * @return bool
     */
    public function setUser(User $user)
    {
        // Prevent any previous identity from being used.
        session_regenerate_id(TRUE);

        $this->_session->user_id = $user->id;

        $this->_user = $user;
        return true;
    }

    /**
     * Masquerading
     */

    /**
     * Become a different user across the application.
     *
     * @param $user_info
     */
    public function masqueradeAsUser($user_info)
    {
        if (!($user_info instanceof User))
            $user_info = $this->_user_repo->findOneBy($user_info);

        $this->_session->masquerade_user_id = $user_info->id;
        $this->_masqueraded_user = $user_info;
    }

    /**
     * Return to the regular authenticated account.
     */
    public function endMasquerade()
    {
        unset($this->_session->masquerade_user_id);
        $this->_masqueraded_user = null;
    }

    /**
     * Return the currently masqueraded user, if one is set.
     *
     * @return User|null
     */
    public function getMasquerade()
    {
        return $this->_masqueraded_user;
    }

    /**
     * Check if the current user is masquerading as another account.
     *
     * @return bool
     */
    public function isMasqueraded()
    {
        if (!$this->isLoggedIn())
        {
            $this->_masqueraded_user = FALSE;
            return false;
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

        return ($this->_masqueraded_user instanceof User);
    }
}