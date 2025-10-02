<?php

declare(strict_types=1);

use App\Controller;
use App\Enums\GlobalPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->get('/', Controller\Frontend\IndexAction::class)
        ->setName('home');

    $app->get('/logout', Controller\Frontend\Account\LogoutAction::class)
        ->setName('account:logout')
        ->add(Middleware\RequireLogin::class);

    $app->get('/login-as/{id}/{csrf}', Controller\Frontend\Account\MasqueradeAction::class)
        ->setName('account:masquerade')
        ->add(new Middleware\Permissions(GlobalPermissions::All))
        ->add(Middleware\RequireLogin::class);

    $app->get('/endsession', Controller\Frontend\Account\EndMasqueradeAction::class)
        ->setName('account:endmasquerade')
        ->add(Middleware\RequireLogin::class);

    $app->map(['GET', 'POST'], '/login', Controller\Frontend\Account\LoginAction::class)
        ->setName('account:login')
        ->add(Middleware\EnableView::class);

    $app->map(['GET', 'POST'], '/login/2fa', Controller\Frontend\Account\TwoFactorAction::class)
        ->setName('account:login:2fa')
        ->add(Middleware\EnableView::class);

    $app->map(['GET', 'POST'], '/forgot', Controller\Frontend\Account\ForgotPasswordAction::class)
        ->setName('account:forgot')
        ->add(Middleware\EnableView::class);

    $app->map(['GET', 'POST'], '/login-token/{token}', Controller\Frontend\Account\LoginTokenAction::class)
        ->setName('account:login-token')
        ->add(Middleware\EnableView::class);

    $app->get('/login/webauthn', Controller\Frontend\Account\WebAuthn\GetValidationAction::class)
        ->setName('account:webauthn');

    $app->post('/login/webauthn', Controller\Frontend\Account\WebAuthn\PostValidationAction::class);

    $app->group(
        '/setup',
        function (RouteCollectorProxy $group) {
            $group->map(['GET', 'POST'], '', Controller\Frontend\SetupController::class . ':indexAction')
                ->setName('setup:index');

            $group->map(['GET', 'POST'], '/complete', Controller\Frontend\SetupController::class . ':completeAction')
                ->setName('setup:complete');

            $group->map(['GET', 'POST'], '/register', Controller\Frontend\SetupController::class . ':registerAction')
                ->setName('setup:register');

            $group->map(['GET', 'POST'], '/station', Controller\Frontend\SetupController::class . ':stationAction')
                ->setName('setup:station');

            $group->map(['GET', 'POST'], '/settings', Controller\Frontend\SetupController::class . ':settingsAction')
                ->setName('setup:settings');
        }
    )->add(Middleware\EnableView::class);

    call_user_func(include(__DIR__ . '/dashboard.php'), $app);
};
