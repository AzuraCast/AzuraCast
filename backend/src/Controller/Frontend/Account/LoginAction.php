<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\User;
use App\Exception\Http\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use App\Utilities\Types;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Psr\Http\Message\ResponseInterface;

final class LoginAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly RateLimit $rateLimit
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $auth = $request->getAuth();
        $acl = $request->getAcl();

        // Check installation completion progress.
        $settings = $this->readSettings();

        if (!$settings->isSetupComplete()) {
            $numUsers = (int)$this->em->createQuery(
                <<<'DQL'
                    SELECT COUNT(u.id) FROM App\Entity\User u
                DQL
            )->getSingleScalarResult();

            if (0 === $numUsers) {
                return $response->withRedirect($request->getRouter()->named('setup:index'));
            }
        }

        if ($auth->isLoggedIn()) {
            return $response->withRedirect($request->getRouter()->named('dashboard'));
        }

        $flash = $request->getFlash();

        if ($request->isPost()) {
            try {
                $this->rateLimit->checkRequestRateLimit($request, 'login', 30, 5);
            } catch (RateLimitExceededException) {
                $flash->error(
                    message: __('You have attempted to log in too many times. Please wait 30 seconds and try again.'),
                    title: __('Too many login attempts'),
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $user = $auth->authenticate(
                Types::string($request->getParam('username')),
                Types::string($request->getParam('password'))
            );

            if ($user instanceof User) {
                $session = $request->getSession();

                // If user selects "remember me", extend the cookie/session lifetime.
                if ($session instanceof SessionCookiePersistenceInterface) {
                    $rememberMe = Types::bool($request->getParam('remember'), false, true);
                    /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                    $session->persistSessionFor(($rememberMe) ? 86400 * 14 : 0);
                }

                // Reload ACL permissions.
                $acl->reload();

                // Persist user as database entity.
                $this->em->persist($user);
                $this->em->flush();

                // Redirect for 2FA.
                if (!$auth->isLoginComplete()) {
                    return $response->withRedirect($request->getRouter()->named('account:login:2fa'));
                }

                // Redirect to complete setup if it's not completed yet.
                if (!$settings->isSetupComplete()) {
                    $flash->success(
                        message: __('Complete the setup process to get started.'),
                        title: __('Logged in successfully.'),
                    );
                    return $response->withRedirect($request->getRouter()->named('setup:index'));
                }

                $flash->success(
                    message: $user->email,
                    title: __('Logged in successfully.')
                );

                $referrer = Types::stringOrNull($session->get('login_referrer'), true);
                return $response->withRedirect(
                    $referrer ?? $request->getRouter()->named('dashboard')
                );
            }

            $flash->error(
                message: __('Your credentials could not be verified.'),
                title: __('Login unsuccessful')
            );

            return $response->withRedirect((string)$request->getUri());
        }

        $customization = $request->getCustomization();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Login',
            id: 'account-login',
            layout: 'minimal',
            title: __('Log In'),
            layoutParams: [
                'page_class' => 'login-content',
            ],
            props: [
                'hideProductName' => $customization->hideProductName(),
                'instanceName' => $customization->getInstanceName(),
                'forgotPasswordUrl' => $router->named('account:forgot'),
                'webAuthnUrl' => $router->named('account:webauthn'),
            ]
        );
    }
}
