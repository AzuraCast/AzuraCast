<?php
namespace App;

use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\NotLoggedIn;
use Azura\Exception;
use Azura\Session;
use Azura\Session\NamespaceInterface;
use Azura\Settings;
use Doctrine\ORM\EntityManager;

class Auth
{
    /** @var int The window of valid one-time passwords outside the current timestamp. */
    public const TOTP_WINDOW = 5;

    /** @var Session */
    protected $session;

    /** @var NamespaceInterface */
    protected $session_namespace;

    /** @var Settings */
    protected $settings;

    /** @var UserRepository */
    protected $user_repo;

    /** @var User|bool|null */
    protected $user;

    /** @var User|bool|null */
    protected $masqueraded_user;

    /**
     * @param Session $session
     * @param EntityManager $em
     * @param Settings $settings
     */
    public function __construct(
        Session $session,
        EntityManager $em,
        Settings $settings
    ) {
        $this->user_repo = $em->getRepository(User::class);

        $this->session = $session;
        $this->session_namespace = $this->session->get('auth');

        $this->settings = $settings;
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
     * Get the currently logged in user.
     *
     * @param bool $real_user_only
     * @return User|null
     * @throws Exception
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

        if (null === $this->masqueraded_user) {
            if (!$this->session_namespace->isset('masquerade_user_id')) {
                $this->masqueraded_user = false;
            } else {
                $mask_user_id = (int)$this->session_namespace->get('masquerade_user_id');
                if (0 !== $mask_user_id) {
                    $user = $this->user_repo->find($mask_user_id);
                } else {
                    $user = null;
                }

                if ($user instanceof User) {
                    $this->masqueraded_user = $user;
                } else {
                    $this->session_namespace->unset('user_id')
                        ->unset('masquerade_user_id');

                    $this->masqueraded_user = false;
                }
            }
        }

        return ($this->masqueraded_user instanceof User);
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
     * Indicate whether login is "complete", i.e. whether any necessary
     * second-factor authentication steps have been completed.
     *
     * @return bool
     */
    public function isLoginComplete(): bool
    {
        return $this->session_namespace->get('login_complete') ?? false;
    }

    /**
     * Get the authenticated user entity.
     *
     * @return User|null
     * @throws Exception
     */
    public function getUser(): ?User
    {
        if (null === $this->user) {
            $user_id = (int)$this->session_namespace->get('user_id');

            if (0 === $user_id) {
                $this->user = false;
                return null;
            }

            $user = $this->user_repo->find($user_id);
            if ($user instanceof User) {
                $this->user = $user;
            } else {
                $this->user = false;
                $this->logout();

                throw new Exception('Invalid user!');
            }
        }

        return ($this->user instanceof User)
            ? $this->user
            : null;
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
        $this->session_namespace->set('login_complete', null === $user->getTwoFactorSecret())
            ->set('user_id', $user->getId());

        $this->user = $user;
    }

    /**
     * End the user's currently logged in session.
     */
    public function logout(): void
    {
        $this->session_namespace
            ->set('login_complete', false)
            ->unset('user_id')
            ->unset('masquerade_user_id');

        $this->user = null;

        $this->session->destroy();
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
            throw new Exception('Invalid user!');
        }

        $this->session_namespace->set('masquerade_user_id', $user_info->getId());

        $this->masqueraded_user = $user_info;
    }

    /**
     * Return to the regular authenticated account.
     */
    public function endMasquerade(): void
    {
        $this->session_namespace->unset('masquerade_user_id');

        $this->masqueraded_user = null;
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
            throw new NotLoggedIn;
        }

        if ($user->verifyTwoFactor($otp)) {
            $this->session_namespace->set('login_complete', true);
            return true;
        }

        return false;
    }
}
