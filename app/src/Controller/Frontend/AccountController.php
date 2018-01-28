<?php
namespace Controller\Frontend;

use Entity\Settings;
use Slim\Http\Request;
use Slim\Http\Response;

class AccountController extends BaseController
{
    /** @var \App\Auth */
    protected $auth;

    public function init()
    {
        if ($this->em->getRepository(Settings::class)->getSetting('setup_complete', 0) == 0) {
            $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();

            if ($num_users == 0) {
                return $this->redirectToRoute(['module' => 'frontend', 'controller' => 'setup']);
            }
        }

        return null;
    }

    protected function preDispatch()
    {
        $this->auth = $this->di[\App\Auth::class];
    }

    public function loginAction(Request $request, Response $response): Response
    {
        if ($this->auth->isLoggedIn()) {
            return $this->redirectHome();
        }

        if (!$_POST) {
            $this->storeReferrer('login', false);
        }

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            try {
                /** @var \AzuraCast\RateLimit $rate_limit */
                $rate_limit = $this->di[\AzuraCast\RateLimit::class];
                $rate_limit->checkRateLimit('login', 30, 5);
            } catch(\AzuraCast\Exception\RateLimitExceeded $e) {
                $this->alert('<b>' . _('Too many login attempts') . '</b><br>' . _('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    'red');

                return $this->redirectHere();
            }

            $login_success = $this->auth->authenticate($_POST['username'], $_POST['password']);

            if ($login_success) {

                // Regenerate session ID
                /** @var \App\Session $session */
                $session = $this->di[\App\Session::class];
                $session->regenerate();

                // Reload ACL permissions.
                $this->acl->reload();

                // Persist user as database entity.
                $user = $this->auth->getLoggedInUser();
                $this->em->persist($user);
                $this->em->flush();

                $this->alert('<b>' . _('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

                $default_url = $this->url->named('home');

                return $this->redirectToStoredReferrer('login', $default_url);
            } else {
                $this->alert('<b>' . _('Login unsuccessful') . '</b><br>' . _('Your credentials could not be verified.'),
                    'red');

                return $this->redirectHere();
            }
        }
    }

    public function logoutAction(Request $request, Response $response): Response
    {
        $this->auth->logout();

        /** @var \App\Session $session */
        $session = $this->di[\App\Session::class];
        $session->destroy();

        return $this->redirectToName('account:login');
    }

    public function endmasqueradeAction(Request $request, Response $response): Response
    {
        $this->doNotRender();

        $this->auth->endMasquerade();

        return $this->redirectToRoute(['module' => 'admin', 'controller' => 'users']);
    }
}
