<?php
namespace App\Controller\Frontend;

use App\Acl;
use App\Auth;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Entity\User;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\RateLimit;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    )
    {
        $this->em = $em;
        $this->auth = $auth;
        $this->rate_limit = $rate_limit;
        $this->acl = $acl;
    }

    public function loginAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Check installation completion progress.

        /** @var SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);
        if ($settings_repo->getSetting(Settings::SETUP_COMPLETE, 0) == 0) {
            $num_users = $this->em->createQuery(/** @lang DQL */'SELECT COUNT(u.id) FROM App\Entity\User u')->getSingleScalarResult();

            if ($num_users == 0) {
                return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('setup:index'));
            }
        }

        if ($this->auth->isLoggedIn()) {
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('dashboard'));
        }

        $session = RequestHelper::getSession($request);

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            try {
                $this->rate_limit->checkRateLimit('login', 30, 5);
            } catch(\Azura\Exception\RateLimitExceeded $e) {
                $session->flash('<b>' . __('Too many login attempts') . '</b><br>' . __('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    'red');

                return ResponseHelper::withRedirect($response, $request->getUri()->getPath());
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
                    return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('account:login:2fa'));
                }

                $session->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $referrer = $session->get('login_referrer');
                if (!empty($referrer->url)) {
                    return ResponseHelper::withRedirect($response, $referrer->url);
                }

                return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('dashboard'));
            }

            $session->flash('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                'red');

            return ResponseHelper::withRedirect($response, $request->getUri());
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'frontend/account/login');
    }

    public function twoFactorAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ('POST' === $request->getMethod()) {
            $session = RequestHelper::getSession($request);

            $parsedBody = $request->getParsedBody();
            $otp = $parsedBody['otp'];

            if ($this->auth->verifyTwoFactor($otp)) {

                $user = $this->auth->getUser();

                $session->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $referrer = $session->get('login_referrer');
                if (!empty($referrer->url)) {
                    return ResponseHelper::withRedirect($response, $referrer->url);
                }

                return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('dashboard'));
            }

            $session->flash('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                'red');

            return ResponseHelper::withRedirect($response, $request->getUri());
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'frontend/account/two_factor');
    }

    public function logoutAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->auth->logout();
        RequestHelper::getSession($request)->destroy();

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('account:login'));
    }

    public function endmasqueradeAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->auth->endMasquerade();

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:users:index'));
    }
}
