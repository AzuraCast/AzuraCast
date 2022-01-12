<?php

use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            call_user_func(include(__DIR__ . '/routes/admin.php'), $group);
            call_user_func(include(__DIR__ . '/routes/base.php'), $group);
            call_user_func(include(__DIR__ . '/routes/public.php'), $group);
            call_user_func(include(__DIR__ . '/routes/stations.php'), $group);
        }
    )->add(Middleware\Auth\StandardAuth::class);

    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            call_user_func(include(__DIR__ . '/routes/api.php'), $group);
        }
    )->add(Middleware\Auth\ApiAuth::class);
};
