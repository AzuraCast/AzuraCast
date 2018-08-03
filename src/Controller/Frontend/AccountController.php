<?php
namespace App\Controller\Frontend;

use App\Acl;
use App\Auth;
use App\Entity\User;
use App\Flash;
use App\Session;
use App\Url;
use App\RateLimit;
use Doctrine\ORM\EntityManager;
use App\Entity\Settings;
use App\Http\Request;
use App\Http\Response;

class AccountController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Auth */
    protected $auth;

    /** @var Session */
    protected $session;

    /** @var Url */
    protected $url;

    /** @var RateLimit */
    protected $rate_limit;

    /** @var Acl */
    protected $acl;

    public function __construct(EntityManager $em, Flash $flash, Auth $auth, Session $session, Url $url, RateLimit $rate_limit, Acl $acl)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->auth = $auth;
        $this->session = $session;
        $this->url = $url;
        $this->rate_limit = $rate_limit;
        $this->acl = $acl;
    }

    public function loginAction(Request $request, Response $response): Response
    {
        // Check installation completion progress.
        if ($this->em->getRepository(Settings::class)->getSetting('setup_complete', 0) == 0) {
            $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM '.User::class.' u')->getSingleScalarResult();

            if ($num_users == 0) {
                return $response->redirectToRoute('setup:index');
            }
        }

        if ($this->auth->isLoggedIn()) {
            return $response->redirectToRoute('dashboard');
        }

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            try {
                $this->rate_limit->checkRateLimit('login', 30, 5);
            } catch(\App\Exception\RateLimitExceeded $e) {
                $this->flash->alert('<b>' . __('Too many login attempts') . '</b><br>' . __('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    'red');

                return $response->redirectHere();
            }

            $login_success = $this->auth->authenticate($_POST['username'], $_POST['password']);

            if ($login_success) {

                // Regenerate session ID
                $this->session->regenerate();

                // Reload ACL permissions.
                $this->acl->reload();

                // Persist user as database entity.
                $user = $this->auth->getLoggedInUser();

                $this->em->persist($user);
                $this->em->flush();

                $this->flash->alert('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $referrer = $this->session->get('login_referrer');
                if (!empty($referrer->url)) {
                    return $response->withRedirect($referrer->url);
                }

                return $response->redirectToRoute('dashboard');
            }

            $this->flash->alert('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                'red');

            return $response->redirectHere();
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/login');
    }

    public function logoutAction(Request $request, Response $response): Response
    {
        $this->auth->logout();
        $this->session->destroy();

        return $response->redirectToRoute('account:login');
    }

    public function endmasqueradeAction(Request $request, Response $response): Response
    {
        $this->auth->endMasquerade();

        return $response->redirectToRoute('admin:users:index');
    }
}
