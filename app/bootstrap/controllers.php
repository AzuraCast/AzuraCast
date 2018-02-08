<?php
return function (\Slim\Container $di, array $app_settings) {

    //
    // Administration Controllers
    //

    $di[Controller\Admin\ApiController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new Controller\Admin\ApiController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->forms->api_key->toArray()
        );
    };

    $di[Controller\Admin\BrandingController::class] = function($di) {
        $config = $di[\App\Config::class];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var \Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(\Entity\Settings::class);

        return new Controller\Admin\BrandingController(
            $settings_repo,
            $di[\App\Flash::class],
            $config->forms->branding->toArray()
        );
    };

    $di[Controller\Admin\IndexController::class] = function($di) {
        return new Controller\Admin\IndexController(
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\AzuraCast\Sync::class]
        );
    };

    $di[Controller\Admin\PermissionsController::class] = function($di) use ($app_settings) {
        $config = $di[\App\Config::class];

        return new Controller\Admin\PermissionsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $app_settings['actions'],
            $config->forms->role->toArray()
        );
    };

    $di[Controller\Admin\SettingsController::class] = function($di) {
        $config = $di[\App\Config::class];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var \Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(\Entity\Settings::class);

        return new Controller\Admin\SettingsController(
            $settings_repo,
            $di[\App\Flash::class],
            $config->forms->settings->toArray()
        );
    };

    $di[Controller\Admin\StationsController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new Controller\Admin\StationsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Cache::class],
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\AzuraCast\Radio\Configuration::class],
            $di[\App\Csrf::class],
            $config->forms->station->toArray(),
            $config->forms->station_clone->toArray()
        );
    };

    $di[Controller\Admin\UsersController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new Controller\Admin\UsersController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Auth::class],
            $di[\App\Csrf::class],
            $config->forms->user->toArray()
        );
    };

    //
    // API Controllers
    //

    $di[\Controller\Api\IndexController::class] = function($di) {
        return new \Controller\Api\IndexController(
            $di[\App\Url::class]
        );
    };

    $di[\Controller\Api\InternalController::class] = function($di) {
        return new \Controller\Api\InternalController(
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Sync\NowPlaying::class]
        );
    };

    $di[\Controller\Api\ListenersController::class] = function($di) {
        return new \Controller\Api\ListenersController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Cache::class]
        );
    };

    $di[\Controller\Api\MediaController::class] = function($di) {
        return new \Controller\Api\MediaController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Url::class]
        );
    };

    $di[\Controller\Api\NowplayingController::class] = function($di) {
        return new \Controller\Api\NowplayingController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Cache::class]
        );
    };

    $di[\Controller\Api\RequestsController::class] = function($di) {
        return new \Controller\Api\RequestsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\App\Url::class]
        );
    };

    $di[\Controller\Api\StationsController::class] = function($di) {
        return new \Controller\Api\StationsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Radio\Adapters::class]
        );
    };

    //
    // Frontend (default) Controllers
    //

    $di[\Controller\Frontend\AccountController::class] = function($di) {
        return new \Controller\Frontend\AccountController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Auth::class],
            $di[\App\Session::class],
            $di[\App\Url::class],
            $di[\AzuraCast\RateLimit::class],
            $di[\AzuraCast\Acl\StationAcl::class]
        );
    };

    $di[\Controller\Frontend\IndexController::class] = function($di) {
        return new \Controller\Frontend\IndexController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\App\Cache::class],
            $di[\InfluxDB\Database::class],
            $di[\AzuraCast\Radio\Adapters::class]
        );
    };

    $di[\Controller\Frontend\ProfileController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Frontend\ProfileController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $config->forms->profile->toArray()
        );
    };

    $di[Controller\Frontend\ApiKeysController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new Controller\Frontend\ApiKeysController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->forms->api_key->toArray()
        );
    };

    $di[\Controller\Frontend\PublicController::class] = function($di) {
        return new \Controller\Frontend\PublicController();
    };

    $di[\Controller\Frontend\SetupController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Frontend\SetupController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Auth::class],
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\AzuraCast\Radio\Configuration::class],
            $config->forms->station->toArray(),
            $config->forms->settings->toArray()
        );
    };

    if (!APP_IN_PRODUCTION) {
        $di[\Controller\Frontend\UtilController::class] = function ($di) {
            return new \Controller\Frontend\UtilController($di);
        };
    }

    //
    // Stations Controllers
    //

    $di[\Controller\Stations\AutomationController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Stations\AutomationController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\AzuraCast\Sync\RadioAutomation::class],
            $config->forms->automation->toArray()
        );
    };

    $di[\Controller\Stations\FilesController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Stations\FilesController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Url::class],
            $di[\App\Csrf::class],
            $config->forms->media->toArray(),
            $config->forms->rename->toArray()
        );
    };

    $di[\Controller\Stations\IndexController::class] = function($di) {
        return new \Controller\Stations\IndexController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Cache::class],
            $di[\InfluxDB\Database::class]
        );
    };

    $di[\Controller\Stations\MountsController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Stations\MountsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            [
                'icecast' => $config->forms->mount_icecast->toArray(),
                'remote' => $config->forms->mount_remote->toArray(),
                'shoutcast2' => $config->forms->mount_shoutcast2->toArray(),
            ]
        );
    };

    $di[\Controller\Stations\PlaylistsController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Stations\PlaylistsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Url::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->forms->playlist->toArray()
        );
    };

    $di[\Controller\Stations\ProfileController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Stations\ProfileController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Cache::class],
            $di[\AzuraCast\Radio\Configuration::class],
            $config->forms->station->toArray()
        );
    };

    $di[\Controller\Stations\ReportsController::class] = function($di) {
        return new \Controller\Stations\ReportsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Cache::class],
            $di[\App\Flash::class],
            $di[\AzuraCast\Sync\RadioAutomation::class]
        );
    };

    $di[\Controller\Stations\RequestsController::class] = function($di) {
        return new \Controller\Stations\RequestsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class]
        );
    };

    $di[\Controller\Stations\StreamersController::class] = function($di) {
        $config = $di[\App\Config::class];

        return new \Controller\Stations\StreamersController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->forms->streamer->toArray()
        );
    };

    $di[\Controller\Stations\UtilController::class] = function($di) {
        return new \Controller\Stations\UtilController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Radio\Configuration::class]
        );
    };

    return $di;

};