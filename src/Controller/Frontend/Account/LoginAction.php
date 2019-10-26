<?php
namespace App\Controller\Frontend\Account;

use App\Acl;
use App\Auth;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Exception\RateLimitExceededException;
use Azura\RateLimit;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class LoginAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Acl $acl,
        Auth $auth,
        EntityManager $em,
        RateLimit $rateLimit,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        // Check installation completion progress.
        if ($settingsRepo->getSetting(Settings::SETUP_COMPLETE, 0) === 0) {
            $num_users = (int)$em->createQuery(/** @lang DQL */ 'SELECT COUNT(u.id) FROM App\Entity\User u')
                ->getSingleScalarResult();

            if (0 === $num_users) {
                return $response->withRedirect($request->getRouter()->named('setup:index'));
            }
        }

        if ($auth->isLoggedIn()) {
            return $response->withRedirect($request->getRouter()->named('dashboard'));
        }

        $flash = $request->getFlash();

        if ($request->isPost()) {
            try {
                $rateLimit->checkRateLimit($request, 'login', 30, 5);
            } catch (RateLimitExceededException $e) {
                $flash->addMessage('<b>' . __('Too many login attempts') . '</b><br>' . __('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    Flash::ERROR);

                return $response->withRedirect($request->getUri()->getPath());
            }

            $user = $auth->authenticate($request->getParam('username'), $request->getParam('password'));

            if ($user instanceof User) {
                // Reload ACL permissions.
                $acl->reload();

                // Persist user as database entity.
                $em->persist($user);
                $em->flush();

                // Redirect for 2FA.
                if (!$auth->isLoginComplete()) {
                    return $response->withRedirect($request->getRouter()->named('account:login:2fa'));
                }

                $flash->addMessage('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
                    Flash::SUCCESS);

                $referrer = $request->getSession()->get('login_referrer');
                if (!empty($referrer)) {
                    return $response->withRedirect($referrer);
                }

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            }

            $flash->addMessage('<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                Flash::ERROR);

            return $response->withRedirect($request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/login');
    }
}