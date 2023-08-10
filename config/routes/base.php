<?php

declare(strict_types=1);

use App\Controller;
use App\Enums\GlobalPermissions;
use App\Enums\StationPermissions;
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

    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            $group->get('/dashboard', Controller\Frontend\DashboardAction::class)
                ->setName('dashboard');

            $group->get('/profile', Controller\Frontend\Profile\IndexAction::class)
                ->setName('profile:index');
        }
    )->add(Middleware\Module\PanelLayout::class)
        ->add(Middleware\EnableView::class)
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
                ->setName('setup:station')
                ->add(Middleware\Module\PanelLayout::class);

            $group->map(['GET', 'POST'], '/settings', Controller\Frontend\SetupController::class . ':settingsAction')
                ->setName('setup:settings')
                ->add(Middleware\Module\PanelLayout::class);
        }
    )->add(Middleware\EnableView::class);

    $app->get('/admin', Controller\Admin\IndexAction::class)
        ->setName('admin:index:index')
        ->add(Middleware\Module\PanelLayout::class)
        ->add(Middleware\EnableView::class)
        ->add(new Middleware\Permissions(GlobalPermissions::View))
        ->add(Middleware\RequireLogin::class);

    $app->get(
        '/station/{station_id}',
        Controller\Stations\IndexAction::class
    )->setName('stations:index:index')
        ->add(Middleware\Module\PanelLayout::class)
        ->add(new Middleware\Permissions(StationPermissions::View, true))
        ->add(Middleware\EnableView::class)
        ->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class)
        ->add(Middleware\RequireLogin::class);
};
