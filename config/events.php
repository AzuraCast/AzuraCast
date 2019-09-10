<?php
use App\Middleware;
use App\Console\Command;

return function (\Azura\EventDispatcher $dispatcher)
{
    // Build default routes and middleware
    $dispatcher->addListener(Azura\Event\BuildRoutes::class, function(Azura\Event\BuildRoutes $event) {
        $app = $event->getApp();

        if (file_exists(__DIR__.'/routes.dev.php')) {
            $dev_routes = require __DIR__.'/routes.dev.php';
            $dev_routes($app);
        }

        $app->add(Middleware\EnforceSecurity::class);
        $app->add(Middleware\InjectAcl::class);
        $app->add(Middleware\GetCurrentUser::class);

    }, 2);

    // Build default menus
    $dispatcher->addListener(App\Event\BuildAdminMenu::class, function(\App\Event\BuildAdminMenu $e) {
        $callable = require(__DIR__.'/menus/admin.php');
        $callable($e);
    });

    $dispatcher->addListener(App\Event\BuildStationMenu::class, function(\App\Event\BuildStationMenu $e) {
        $callable = require(__DIR__.'/menus/station.php');
        $callable($e);
    });

    // Other event subscribers from across the application.
    $dispatcher->addServiceSubscriber([
        \App\Radio\AutoDJ::class,
        \App\Radio\Backend\Liquidsoap::class,
        \App\Sync\Task\NowPlaying::class,
        \App\Webhook\Dispatcher::class,
        \App\Controller\Api\NowplayingController::class,
        \App\Notification\Manager::class,
    ]);

};
