<?php

declare(strict_types=1);

use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            call_user_func(include(__DIR__ . '/routes/public.php'), $group);
        }
    )->add(Middleware\Auth\PublicAuth::class);

    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            call_user_func(include(__DIR__ . '/routes/base.php'), $group);
        }
    )->add(Middleware\Auth\StandardAuth::class)
        ->add(Middleware\InjectSession::class);

    $app->group(
        '/api',
        function (RouteCollectorProxy $group) {
            $group->group(
                '',
                function (RouteCollectorProxy $group) {
                    call_user_func(include(__DIR__ . '/routes/api_public.php'), $group);
                }
            )->add(Middleware\Module\Api::class)
                ->add(Middleware\Auth\PublicAuth::class);

            $group->group(
                '',
                function (RouteCollectorProxy $group) {
                    call_user_func(include(__DIR__ . '/routes/api_internal.php'), $group);
                    call_user_func(include(__DIR__ . '/routes/api_admin.php'), $group);
                    call_user_func(include(__DIR__ . '/routes/api_frontend.php'), $group);
                    call_user_func(include(__DIR__ . '/routes/api_station.php'), $group);
                }
            )
                ->add(Middleware\Module\Api::class)
                ->add(Middleware\Auth\ApiAuth::class)
                ->add(Middleware\InjectSession::class);
        }
    );
};
