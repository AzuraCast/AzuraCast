<?php
namespace Controller\Frontend;

use App\Acl;
use App\Auth;
use App\Flash;
use App\Session;
use App\Url;
use AzuraCast\RateLimit;
use Doctrine\ORM\EntityManager;
use Entity\Settings;
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
            $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();

            if ($num_users == 0) {
                return $response->redirectToRoute('setup:index');
            }
        }

        if ($this->auth->isLoggedIn()) {
            return $response->redirectHome();
        }

        if (!$_POST) {
            $this->storeReferrer('login', false);
        }

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            try {
                $this->rate_limit->checkRateLimit('login', 30, 5);
            } catch(\AzuraCast\Exception\RateLimitExceeded $e) {
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

                $referrer = $this->getStoredReferrer();
                if ($referrer) {
                    return $response->withRedirect($referrer);
                }

                return $response->redirectHome();
            }

            $this->flash->alert('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                'red');

            return $response->redirectHere();
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'frontend/account/login');
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

    /**
     * Store the current referring page in a session variable.
     *
     * @param string $namespace
     * @param bool $loose
     */
    protected function storeReferrer($namespace = 'default', $loose = true)
    {
        $session = $this->_getReferrerStorage($namespace);

        if (!isset($session->url) || ($loose && isset($session->url) && $this->url->current() != $this->url->referrer())) {
            $session->url = $this->url->referrer();
        }
    }

    /**
     * Retrieve the referring page stored in a session variable (if it exists).
     *
     * @param string $namespace
     * @return mixed
     */
    protected function getStoredReferrer($namespace = 'default')
    {
        $session = $this->_getReferrerStorage($namespace);
        return $session->url;
    }

    /**
     * Clear any session variable storing referrer data.
     *
     * @param string $namespace
     */
    protected function clearStoredReferrer($namespace = 'default')
    {
        $session = $this->_getReferrerStorage($namespace);
        unset($session->url);
    }

    /**
     * @param string $namespace
     * @return \App\Session\NamespaceInterface
     */
    protected function _getReferrerStorage($namespace = 'default'): \App\Session\NamespaceInterface
    {
        return $this->session->get('referrer_' . $namespace);
    }
}
