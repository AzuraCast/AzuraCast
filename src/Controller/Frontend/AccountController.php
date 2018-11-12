<?php
namespace App\Controller\Frontend;

use App\Acl;
use App\Auth;
use App\Entity\Repository\SettingsRepository;
use App\Entity\User;
use Azura\RateLimit;
use Doctrine\ORM\EntityManager;
use App\Entity\Settings;
use App\Http\Request;
use App\Http\Response;

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
     * @see \App\Provider\FrontendProvider
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

    public function loginAction(Request $request, Response $response): Response
    {
        // Check installation completion progress.

        /** @var SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);
        if ($settings_repo->getSetting('setup_complete', 0) == 0) {
            $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM '.User::class.' u')->getSingleScalarResult();

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
                $this->rate_limit->checkRateLimit('login', 30, 5);
            } catch(\Azura\Exception\RateLimitExceeded $e) {
                $session->flash('<b>' . __('Too many login attempts') . '</b><br>' . __('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    'red');

                return $response->withRedirect($request->getUri()->getPath());
            }

            $login_success = $this->auth->authenticate($_POST['username'], $_POST['password']);

            if ($login_success) {

                // Regenerate session ID
                $session->regenerate();

                // Reload ACL permissions.
                $this->acl->reload();

                // Persist user as database entity.
                $user = $this->auth->getLoggedInUser();

                $this->em->persist($user);
                $this->em->flush();

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

    public function logoutAction(Request $request, Response $response): Response
    {
        $this->auth->logout();
        $request->getSession()->destroy();

        return $response->withRedirect($request->getRouter()->named('account:login'));
    }

    public function endmasqueradeAction(Request $request, Response $response): Response
    {
        $this->auth->endMasquerade();

        return $response->withRedirect($request->getRouter()->named('admin:users:index'));
    }
}
