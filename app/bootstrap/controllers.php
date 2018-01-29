<?php
return function (\Slim\Container $di) {

    // Administration Controllers

    $di[Controller\Admin\ApiController::class] = function($di) {
        return new Controller\Admin\ApiController(

        );
    };

    $di[Controller\Admin\BrandingController::class] = function($di) {
        return new Controller\Admin\BrandingController(

        );
    };

    $di[Controller\Admin\IndexController::class] = function($di) {
        return new Controller\Admin\IndexController(

        );
    };

    $di[Controller\Admin\PermissionsController::class] = function($di) {
        return new Controller\Admin\PermissionsController(

        );
    };

    $di[Controller\Admin\SettingsController::class] = function($di) {
        return new Controller\Admin\SettingsController(

        );
    };

    $di[Controller\Admin\StationsController::class] = function($di) {
        return new Controller\Admin\StationsController(

        );
    };

    $di[Controller\Admin\UsersController::class] = function($di) {
        return new Controller\Admin\UsersController(

        );
    };

    // API Controllers

    $di[\Controller\Api\IndexController::class] = function($di) {
        return new \Controller\Api\IndexController(

        );
    };

    $di[\Controller\Api\InternalController::class] = function($di) {
        return new \Controller\Api\InternalController(

        );
    };

    $di[\Controller\Api\ListenersController::class] = function($di) {
        return new \Controller\Api\ListenersController(

        );
    };

    $di[\Controller\Api\MediaController::class] = function($di) {
        return new \Controller\Api\MediaController(

        );
    };

    $di[\Controller\Api\NowplayingController::class] = function($di) {
        return new \Controller\Api\NowplayingController(

        );
    };

    $di[\Controller\Api\RequestsController::class] = function($di) {
        return new \Controller\Api\RequestsController(

        );
    };

    $di[\Controller\Api\StationsController::class] = function($di) {
        return new \Controller\Api\StationsController(

        );
    };

    // Frontend (default) Controllers

    $di[\Controller\Frontend\AccountController::class] = function($di) {
        return new \Controller\Frontend\AccountController(

        );
    };

    $di[\Controller\Frontend\IndexController::class] = function($di) {
        return new \Controller\Frontend\IndexController(

        );
    };

    $di[\Controller\Frontend\ProfileController::class] = function($di) {
        return new \Controller\Frontend\ProfileController(

        );
    };

    $di[\Controller\Frontend\PublicController::class] = function($di) {
        return new \Controller\Frontend\PublicController(

        );
    };

    $di[\Controller\Frontend\SetupController::class] = function($di) {
        return new \Controller\Frontend\SetupController(

        );
    };

    if (!APP_IN_PRODUCTION) {
        $di[\Controller\Frontend\UtilController::class] = function ($di) {
            return new \Controller\Frontend\UtilController();
        };
    }

    // Stations Controllers

    $di[\Controller\Stations\AutomationController::class] = function($di) {
        return new \Controller\Stations\AutomationController(

        );
    };

    $di[\Controller\Stations\FilesController::class] = function($di) {
        return new \Controller\Stations\FilesController(

        );
    };

    $di[\Controller\Stations\IndexController::class] = function($di) {
        return new \Controller\Stations\IndexController(

        );
    };

    $di[\Controller\Stations\MountsController::class] = function($di) {
        return new \Controller\Stations\MountsController(

        );
    };

    $di[\Controller\Stations\PlaylistsController::class] = function($di) {
        return new \Controller\Stations\PlaylistsController(

        );
    };

    $di[\Controller\Stations\ProfileController::class] = function($di) {
        return new \Controller\Stations\ProfileController(

        );
    };

    $di[\Controller\Stations\ReportsController::class] = function($di) {
        return new \Controller\Stations\ReportsController(

        );
    };

    $di[\Controller\Stations\RequestsController::class] = function($di) {
        return new \Controller\Stations\RequestsController(

        );
    };

    $di[\Controller\Stations\StreamersController::class] = function($di) {
        return new \Controller\Stations\StreamersController(

        );
    };

    $di[\Controller\Stations\UtilController::class] = function($di) {
        return new \Controller\Stations\UtilController(

        );
    };

    return $di;

};