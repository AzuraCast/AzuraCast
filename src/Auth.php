<?php
namespace App;

use App\Entity\Repository\UserRepository;
use App\Entity\User;
use Azura\Session;
use Azura\Session\NamespaceInterface;

class Auth
{
    /** @var int The window of valid one-time passwords outside the current timestamp. */
    public const TOTP_WINDOW = 5;

    /** @var NamespaceInterface */
    protected $_session;

    /** @var UserRepository */
    protected $_user_repo;

    /** @var User|bool|null */
    protected $_user;

    /** @var User|bool|null */
    protected $_masqueraded_user;

    public function __construct(Session $session, UserRepository $user_repo)
    {
        $this->_user_repo = $user_repo;

        $class_name = strtolower(str_replace(['\\', '_'], ['', ''], get_called_class()));
        $this->_session = $session->get('auth_' . $class_name . '_user');
    }

    /**
     * Authenticate a given username and password combination against the User repository.
     *
     * @param string $username
     * @param string $password
     * @return User|null
     */
    public function authenticate($username, $password): ?User
    {
        $user_auth = $this->_user_repo->authenticate($username, $password);

        if ($user_auth instanceof User) {
            $this->setUser($user_auth);
            return $user_auth;
        }

        return null;
    }

    /**
     * End the user's currently logged in session.
     */
    public function logout(): void
    {
        $this->_session->login_complete = false;

        unset($this->_session->user_id);
        unset($this->_session->masquerade_user_id);

        $this->_user = null;

        @session_unset();
    }

    /**
     * Check if a user account is currently authenticated.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (APP_IS_COMMAND_LINE && !APP_TESTING_MODE) {
            return false;
        }

        if (!$this->isLoginComplete()) {
            return false;
        }

        $user = $this->getUser();
        return ($user instanceof User);
    }

    /**
     * Get the currently logged in user.
     *
     * @param bool $real_user_only
     * @return User|null
     * @throws \Azura\Exception
     */
    public function getLoggedInUser($real_user_only = false): ?User
    {
        if (!$real_user_only && $this->isMasqueraded()) {
            return $this->getMasquerade();
        }

        if (!$this->isLoginComplete()) {
            return null;
        }

        return $this->getUser();
    }

    /**
     * Get the authenticated user entity.
     *
     * @return User|null
     * @throws \Azura\Exception
     */
    public function getUser(): ?User
    {
        if ($this->_user === null) {
            $user_id = (int)$this->_session->user_id;

            if (0 === $user_id) {
                $this->_user = false;
                return null;
            }

            $user = $this->_user_repo->find($user_id);
            if ($user instanceof User) {
                $this->_user = $user;
            } else {
                unset($this->_session->user_id);
                $this->_user = false;
                $this->logout();

                throw new \Azura\Exception('Invalid user!');
            }
        }

        return ($this->_user instanceof User)
            ? $this->_user
            : null;
    }

    /**
     * Set the currently authenticated user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->_session->login_complete = (null === $user->getTwoFactorSecret());
        $this->_session->user_id = $user->getId();
        $this->_user = $user;
    }

    /**
     * Masquerading
     */

    /**
     * Become a different user across the application.
     *
     * @param array $user_info
     */
    public function masqueradeAsUser($user_info): void
    {
        if (!($user_info instanceof User)) {
            $user_info = $this->_user_repo->findOneBy($user_info);
        }

        if (!($user_info instanceof User)) {
            throw new \Azura\Exception('Invalid user!');
        }

        $this->_session->masquerade_user_id = $user_info->getId();
        $this->_masqueraded_user = $user_info;
    }

    /**
     * Return to the regular authenticated account.
     */
    public function endMasquerade(): void
    {
        unset($this->_session->masquerade_user_id);
        $this->_masqueraded_user = null;
    }

    /**
     * Return the currently masqueraded user, if one is set.
     *
     * @return User|null
     */
    public function getMasquerade(): ?User
    {
        return $this->_masqueraded_user;
    }

    /**
     * Check if the current user is masquerading as another account.
     *
     * @return bool
     */
    public function isMasqueraded(): bool
    {
        if (!$this->isLoggedIn()) {
            $this->_masqueraded_user = false;
            return false;
        }

        if ($this->_masqueraded_user === null) {
            if (!$this->_session->masquerade_user_id) {
                $this->_masqueraded_user = false;
            } else {
                $mask_user_id = (int)$this->_session->masquerade_user_id;
                if (0 !== $mask_user_id) {
                    $user = $this->_user_repo->find($mask_user_id);
                } else {
                    $user = null;
                }

                if ($user instanceof User) {
                    $this->_masqueraded_user = $user;
                } else {
                    unset($this->_session->user_id, $this->_session->masquerade_user_id);
                    $this->_masqueraded_user = false;
                }
            }
        }

        return ($this->_masqueraded_user instanceof User);
    }

    /**
     * Indicate whether login is "complete", i.e. whether any necessary
     * second-factor authentication steps have been completed.
     *
     * @return bool
     */
    public function isLoginComplete(): bool
    {
        return $this->_session->login_complete ?? false;
    }

    /**
     * Verify a supplied one-time password.
     *
     * @param string $otp
     * @return bool
     */
    public function verifyTwoFactor(string $otp): bool
    {
        $user = $this->getUser();

        if (!($user instanceof User)) {
            throw new \App\Exception\NotLoggedIn;
        }

        if ($user->verifyTwoFactor($otp)) {
            $this->_session->login_complete = true;
            return true;
        }

        return false;
    }
}
