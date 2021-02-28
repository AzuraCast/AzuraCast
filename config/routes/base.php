<?php

use App\Controller;
use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/', Controller\Frontend\IndexAction::class)
        ->setName('home');

    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            $group->get('/dashboard', Controller\Frontend\DashboardAction::class)
                ->setName('dashboard');

            $group->get('/logout', Controller\Frontend\Account\LogoutAction::class)
                ->setName('account:logout');

            $group->get('/endsession', Controller\Frontend\Account\EndMasqueradeAction::class)
                ->setName('account:endmasquerade');

            $group->get('/profile', Controller\Frontend\Profile\IndexAction::class)
                ->setName('profile:index');

            $group->map(['GET', 'POST'], '/profile/edit', Controller\Frontend\Profile\EditAction::class)
                ->setName('profile:edit');

            $group->map(
                ['GET', 'POST'],
                '/profile/2fa/enable',
                Controller\Frontend\Profile\EnableTwoFactorAction::class
            )
                ->setName('profile:2fa:enable');

            $group->map(
                ['GET', 'POST'],
                '/profile/2fa/disable',
                Controller\Frontend\Profile\DisableTwoFactorAction::class
            )
                ->setName('profile:2fa:disable');

            $group->get('/profile/theme', Controller\Frontend\Profile\ThemeAction::class)
                ->setName('profile:theme');

            $group->get('/api_keys', Controller\Frontend\ApiKeysController::class . ':indexAction')
                ->setName('api_keys:index');

            $group->map(
                ['GET', 'POST'],
                '/api_keys/edit/{id}',
                Controller\Frontend\ApiKeysController::class . ':editAction'
            )
                ->setName('api_keys:edit');

            $group->map(['GET', 'POST'], '/api_keys/add', Controller\Frontend\ApiKeysController::class . ':editAction')
                ->setName('api_keys:add');

            $group->get('/api_keys/delete/{id}/{csrf}', Controller\Frontend\ApiKeysController::class . ':deleteAction')
                ->setName('api_keys:delete');
        }
    )->add(Middleware\EnableView::class)
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

    $app->map(['GET', 'POST'], '/recover/{token}', Controller\Frontend\Account\RecoverAction::class)
        ->setName('account:recover')
        ->add(Middleware\EnableView::class);

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
};
