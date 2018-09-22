<?php

return function (\App\EventDispatcher $dispatcher)
{
    // Build default routes and middleware
    $dispatcher->addListener(\App\Event\BuildRoutes::NAME, function(\App\Event\BuildRoutes $event) {
        $app = $event->getApp();

        // Get the current user entity object and assign it into the request if it exists.
        $app->add(\App\Middleware\GetCurrentUser::class);

        // Inject the application router into the request object.
        $app->add(\App\Middleware\EnableRouter::class);

        // Inject the session manager into the request object.
        $app->add(\App\Middleware\EnableSession::class);

        // Check HTTPS setting and enforce Content Security Policy accordingly.
        $app->add(\App\Middleware\EnforceSecurity::class);

        // Remove trailing slash from all URLs when routing.
        $app->add(\App\Middleware\RemoveSlashes::class);
    }, 1);

    $dispatcher->addListener(\App\Event\BuildRoutes::NAME, function(\App\Event\BuildRoutes $event) {
        call_user_func(include(__DIR__.'/routes.php'), $event->getApp());
    }, 0);

    // Other event subscribers from across the application.
    $dispatcher->addServiceSubscriber([
        \App\Radio\AutoDJ::class,
        \App\Radio\Backend\Liquidsoap::class,
        \App\Sync\Task\NowPlaying::class,
        \App\Webhook\Dispatcher::class,
    ]);

};
