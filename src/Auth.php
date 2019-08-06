<?php
namespace App;

use App\Entity\Repository\UserRepository;
use App\Entity\User;
use Azura\Session;
use Azura\Session\NamespaceInterface;
use Doctrine\ORM\EntityManager;

class Auth
{
    /** @var int The window of valid one-time passwords outside the current timestamp. */
    public const TOTP_WINDOW = 5;

    /** @var NamespaceInterface */
    protected $session;

    /** @var UserRepository */
    protected $user_repo;

    /** @var User|bool|null */
    protected $user;

    /** @var User|bool|null */
    protected $masqueraded_user;

    /**
     * @param Session $session
     * @param EntityManager $em
     */
    public function __construct(Session $session, EntityManager $em)
    {
        $this->user_repo = $em->getRepository(User::class);

        $class_name = strtolower(str_replace(['\\', '_'], ['', ''], static::class));
        $this->session = $session->get('auth_' . $class_name . '_user');
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
        $user_auth = $this->user_repo->authenticate($username, $password);

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
        $this->session->login_complete = false;

        unset($this->session->user_id);
        unset($this->session->masquerade_user_id);

        $this->user = null;

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
        if ($this->user === null) {
            $user_id = (int)$this->session->user_id;

            if (0 === $user_id) {
                $this->user = false;
                return null;
            }

            $user = $this->user_repo->find($user_id);
            if ($user instanceof User) {
                $this->user = $user;
            } else {
                unset($this->session->user_id);
                $this->user = false;
                $this->logout();

                throw new \Azura\Exception('Invalid user!');
            }
        }

        return ($this->user instanceof User)
            ? $this->user
            : null;
    }

    /**
     * Set the currently authenticated user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->session->login_complete = (null === $user->getTwoFactorSecret());
        $this->session->user_id = $user->getId();
        $this->user = $user;
    }

    /**
     * Masquerading
     */

    /**
     * Become a different user across the application.
     *
     * @param User|array $user_info
     */
    public function masqueradeAsUser($user_info): void
    {
        if (!($user_info instanceof User)) {
            $user_info = $this->user_repo->findOneBy($user_info);
        }

        if (!($user_info instanceof User)) {
            throw new \Azura\Exception('Invalid user!');
        }

        $this->session->masquerade_user_id = $user_info->getId();
        $this->masqueraded_user = $user_info;
    }

    /**
     * Return to the regular authenticated account.
     */
    public function endMasquerade(): void
    {
        unset($this->session->masquerade_user_id);
        $this->masqueraded_user = null;
    }

    /**
     * Return the currently masqueraded user, if one is set.
     *
     * @return User|null
     */
    public function getMasquerade(): ?User
    {
        return $this->masqueraded_user;
    }

    /**
     * Check if the current user is masquerading as another account.
     *
     * @return bool
     */
    public function isMasqueraded(): bool
    {
        if (!$this->isLoggedIn()) {
            $this->masqueraded_user = false;
            return false;
        }

        if ($this->masqueraded_user === null) {
            if (!$this->session->masquerade_user_id) {
                $this->masqueraded_user = false;
            } else {
                $mask_user_id = (int)$this->session->masquerade_user_id;
                if (0 !== $mask_user_id) {
                    $user = $this->user_repo->find($mask_user_id);
                } else {
                    $user = null;
                }

                if ($user instanceof User) {
                    $this->masqueraded_user = $user;
                } else {
                    unset($this->session->user_id, $this->session->masquerade_user_id);
                    $this->masqueraded_user = false;
                }
            }
        }

        return ($this->masqueraded_user instanceof User);
    }

    /**
     * Indicate whether login is "complete", i.e. whether any necessary
     * second-factor authentication steps have been completed.
     *
     * @return bool
     */
    public function isLoginComplete(): bool
    {
        return $this->session->login_complete ?? false;
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
            $this->session->login_complete = true;
            return true;
        }

        return false;
    }
}
