<?php

declare(strict_types=1);

namespace App;

use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\NotLoggedInException;
use Mezzio\Session\SessionInterface;

class Auth
{
    public const SESSION_IS_LOGIN_COMPLETE_KEY = 'is_login_complete';
    public const SESSION_USER_ID_KEY = 'user_id';
    public const SESSION_MASQUERADE_USER_ID_KEY = 'masquerade_user_id';

    /** @var int The window of valid one-time passwords outside the current timestamp. */
    public const TOTP_WINDOW = 5;

    protected bool|User|null $user = null;

    protected bool|User|null $masqueraded_user = null;

    public function __construct(
        protected UserRepository $userRepo,
        protected SessionInterface $session,
        protected Environment $environment
    ) {
    }

    /**
     * Authenticate a given username and password combination against the User repository.
     *
     * @param string $username
     * @param string $password
     */
    public function authenticate(string $username, string $password): ?User
    {
        $user_auth = $this->userRepo->authenticate($username, $password);

        if ($user_auth instanceof User) {
            $this->setUser($user_auth);
            return $user_auth;
        }

        return null;
    }

    /**
     * Get the currently logged in user.
     *
     * @param bool $real_user_only
     *
     * @throws Exception
     */
    public function getLoggedInUser(bool $real_user_only = false): ?User
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
     * Check if the current user is masquerading as another account.
     */
    public function isMasqueraded(): bool
    {
        if (!$this->isLoggedIn()) {
            $this->masqueraded_user = false;
            return false;
        }

        if (null === $this->masqueraded_user) {
            if (!$this->session->has(self::SESSION_MASQUERADE_USER_ID_KEY)) {
                $this->masqueraded_user = false;
            } else {
                $mask_user_id = (int)$this->session->get(self::SESSION_MASQUERADE_USER_ID_KEY);
                if (0 !== $mask_user_id) {
                    $user = $this->userRepo->getRepository()->find($mask_user_id);
                } else {
                    $user = null;
                }

                if ($user instanceof User) {
                    $this->masqueraded_user = $user;
                } else {
                    $this->session->clear();
                    $this->masqueraded_user = false;
                }
            }
        }

        return ($this->masqueraded_user instanceof User);
    }

    /**
     * Check if a user account is currently authenticated.
     */
    public function isLoggedIn(): bool
    {
        if ($this->environment->isCli() && !$this->environment->isTesting()) {
            return false;
        }

        if (!$this->isLoginComplete()) {
            return false;
        }

        $user = $this->getUser();
        return ($user instanceof User);
    }

    /**
     * Indicate whether login is "complete", i.e. whether any necessary
     * second-factor authentication steps have been completed.
     */
    public function isLoginComplete(): bool
    {
        return $this->session->get(self::SESSION_IS_LOGIN_COMPLETE_KEY, false) ?? false;
    }

    /**
     * Get the authenticated user entity.
     *
     * @throws Exception
     */
    public function getUser(): ?User
    {
        if (null === $this->user) {
            $user_id = (int)$this->session->get(self::SESSION_USER_ID_KEY);

            if (0 === $user_id) {
                $this->user = false;
                return null;
            }

            $user = $this->userRepo->getRepository()->find($user_id);
            if ($user instanceof User) {
                $this->user = $user;
            } else {
                $this->user = false;
                $this->logout();

                throw new Exception('Invalid user!');
            }
        }

        if (!$this->user instanceof User) {
            return null;
        }

        /** @var User|null $user */
        $user = $this->userRepo->getRepository()->find($this->user->getId());
        return $user;
    }

    /**
     * Masquerading
     */

    /**
     * Set the currently authenticated user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->session->set(self::SESSION_IS_LOGIN_COMPLETE_KEY, null === $user->getTwoFactorSecret());
        $this->session->set(self::SESSION_USER_ID_KEY, $user->getId());

        $this->user = $user;
    }

    /**
     * End the user's currently logged in session.
     */
    public function logout(): void
    {
        if (isset($this->session) && $this->session instanceof SessionInterface) {
            $this->session->clear();
        }

        $this->user = null;
    }

    /**
     * Return the currently masqueraded user, if one is set.
     */
    public function getMasquerade(): ?User
    {
        if ($this->masqueraded_user instanceof User) {
            return $this->masqueraded_user;
        }
        return null;
    }

    /**
     * Become a different user across the application.
     *
     * @param array<string, mixed>|User $user_info
     */
    public function masqueradeAsUser(User|array $user_info): void
    {
        if (!($user_info instanceof User)) {
            $user_info = $this->userRepo->getRepository()->findOneBy($user_info);
        }

        if (!($user_info instanceof User)) {
            throw new Exception('Invalid user!');
        }

        $this->session->set(self::SESSION_MASQUERADE_USER_ID_KEY, $user_info->getId());
        $this->masqueraded_user = $user_info;
    }

    /**
     * Return to the regular authenticated account.
     */
    public function endMasquerade(): void
    {
        $this->session->unset(self::SESSION_MASQUERADE_USER_ID_KEY);
        $this->masqueraded_user = null;
    }

    /**
     * Verify a supplied one-time password.
     *
     * @param string $otp
     */
    public function verifyTwoFactor(string $otp): bool
    {
        $user = $this->getUser();

        if (!($user instanceof User)) {
            throw new NotLoggedInException();
        }

        if ($user->verifyTwoFactor($otp)) {
            $this->session->set(self::SESSION_IS_LOGIN_COMPLETE_KEY, true);
            return true;
        }

        return false;
    }
}
