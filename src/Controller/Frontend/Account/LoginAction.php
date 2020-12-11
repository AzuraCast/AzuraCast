<?php

namespace App\Controller\Frontend\Account;

use App\Entity\Repository\SettingsRepository;
use App\Entity\User;
use App\Exception\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Psr\Http\Message\ResponseInterface;

class LoginAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        RateLimit $rateLimit,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $auth = $request->getAuth();
        $acl = $request->getAcl();

        // Check installation completion progress.
        $settings = $settingsRepo->readSettings();

        if (!$settings->isSetupComplete()) {
            $num_users = (int)$em->createQuery(
                <<<'DQL'
                    SELECT COUNT(u.id) FROM App\Entity\User u
                DQL
            )->getSingleScalarResult();

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
                $flash->addMessage(
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Too many login attempts'),
                        __('You have attempted to log in too many times. Please wait 30 seconds and try again.')
                    ),
                    Flash::ERROR
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $user = $auth->authenticate($request->getParam('username'), $request->getParam('password'));

            if ($user instanceof User) {
                // If user selects "remember me", extend the cookie/session lifetime.
                $session = $request->getSession();
                if ($session instanceof SessionCookiePersistenceInterface) {
                    $rememberMe = (bool)$request->getParam('remember', 0);
                    $session->persistSessionFor(($rememberMe) ? 86400 * 14 : 0);
                }

                // Reload ACL permissions.
                $acl->reload();

                // Persist user as database entity.
                $em->persist($user);
                $em->flush();

                // Redirect for 2FA.
                if (!$auth->isLoginComplete()) {
                    return $response->withRedirect($request->getRouter()->named('account:login:2fa'));
                }

                // Redirect to complete setup if it's not completed yet.
                if (!$settings->isSetupComplete()) {
                    $flash->addMessage(
                        sprintf(
                            '<b>%s</b><br>%s',
                            __('Logged in successfully.'),
                            __('Complete the setup process to get started.')
                        ),
                        Flash::SUCCESS
                    );
                    return $response->withRedirect($request->getRouter()->named('setup:index'));
                }

                $flash->addMessage(
                    '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
                    Flash::SUCCESS
                );

                $referrer = $request->getSession()->get('login_referrer');
                if (!empty($referrer)) {
                    return $response->withRedirect($referrer);
                }

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            }

            $flash->addMessage(
                '<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                Flash::ERROR
            );

            return $response->withRedirect($request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/login');
    }
}
