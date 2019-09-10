<?php
namespace App\Controller\Frontend;

use App\Acl;
use App\Auth;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Exception\RateLimitExceededException;
use Azura\RateLimit;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class AccountController
{
    /** @var EntityManager */
    protected $em;

    /** @var Auth */
    protected $auth;

    /** @var RateLimit */
    protected $rate_limit;

    /** @var Acl */
    protected $acl;

    /**
     * @param EntityManager $em
     * @param Auth $auth
     * @param RateLimit $rate_limit
     * @param Acl $acl
     */
    public function __construct(
        EntityManager $em,
        Auth $auth,
        RateLimit $rate_limit,
        Acl $acl
    ) {
        $this->em = $em;
        $this->auth = $auth;
        $this->rate_limit = $rate_limit;
        $this->acl = $acl;
    }

    public function loginAction(ServerRequest $request, Response $response): ResponseInterface
    {
        // Check installation completion progress.

        /** @var SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);
        if ($settings_repo->getSetting(Settings::SETUP_COMPLETE, 0) == 0) {
            $num_users = $this->em->createQuery(/** @lang DQL */ 'SELECT COUNT(u.id) FROM App\Entity\User u')->getSingleScalarResult();

            if ($num_users == 0) {
                return $response->withRedirect($request->getRouter()->named('setup:index'));
            }
        }

        if ($this->auth->isLoggedIn()) {
            return $response->withRedirect($request->getRouter()->named('dashboard'));
        }

        $session = $request->getSession();

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            try {
                $this->rate_limit->checkRateLimit($request, 'login', 30, 5);
            } catch (RateLimitExceededException $e) {
                $session->flash('<b>' . __('Too many login attempts') . '</b><br>' . __('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    'red');

                return $response->withRedirect($request->getUri()->getPath());
            }

            $user = $this->auth->authenticate($_POST['username'], $_POST['password']);

            if ($user instanceof User) {
                // Regenerate session ID
                $session->regenerate();

                // Reload ACL permissions.
                $this->acl->reload();

                // Persist user as database entity.
                $this->em->persist($user);
                $this->em->flush();

                // Redirect for 2FA.
                if (!$this->auth->isLoginComplete()) {
                    return $response->withRedirect($request->getRouter()->named('account:login:2fa'));
                }

                $session->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $referrer = $session->get('login_referrer');
                if (!empty($referrer->url)) {
                    return $response->withRedirect($referrer->url);
                }

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            }

            $session->flash('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                'red');

            return $response->withRedirect($request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/login');
    }

    public function twoFactorAction(ServerRequest $request, Response $response): ResponseInterface
    {
        if ('POST' === $request->getMethod()) {
            $session = $request->getSession();

            $parsedBody = $request->getParsedBody();
            $otp = $parsedBody['otp'];

            if ($this->auth->verifyTwoFactor($otp)) {

                $user = $this->auth->getUser();

                $session->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $referrer = $session->get('login_referrer');
                if (!empty($referrer->url)) {
                    return $response->withRedirect($referrer->url);
                }

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            }

            $session->flash('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                'red');

            return $response->withRedirect($request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/two_factor');
    }

    public function logoutAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->auth->logout();
        $request->getSession()->destroy();

        return $response->withRedirect($request->getRouter()->named('account:login'));
    }

    public function endmasqueradeAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->auth->endMasquerade();

        return $response->withRedirect($request->getRouter()->named('admin:users:index'));
    }
}
