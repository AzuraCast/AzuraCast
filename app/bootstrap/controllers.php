<?php
return function (\Slim\Container $di, array $app_settings) {

    //
    // Administration Controllers
    //

    $di[Controller\Admin\ApiController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new Controller\Admin\ApiController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->get('forms/api_key')
        );
    };

    $di[Controller\Admin\BrandingController::class] = function($di) use ($app_settings) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new Controller\Admin\BrandingController(
            $di[\Entity\Repository\SettingsRepository::class],
            $di[\App\Flash::class],
            $config->get('forms/branding', ['settings' => $app_settings])
        );
    };

    $di[Controller\Admin\IndexController::class] = function($di) {
        return new Controller\Admin\IndexController(
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\AzuraCast\Sync::class]
        );
    };

    $di[Controller\Admin\PermissionsController::class] = function($di) use ($app_settings) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var \Entity\Repository\StationRepository $stations_repo */
        $stations_repo = $em->getRepository(\Entity\Station::class);

        $actions = $config->get('admin/actions');

        return new Controller\Admin\PermissionsController(
            $em,
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $actions,
            $config->get('forms/role', [
                'actions' => $actions,
                'all_stations' => $stations_repo->fetchArray(),
            ])
        );
    };

    $di[Controller\Admin\SettingsController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new Controller\Admin\SettingsController(
            $di[\Entity\Repository\SettingsRepository::class],
            $di[\App\Flash::class],
            $config->get('forms/settings')
        );
    };

    $di[Controller\Admin\StationsController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new Controller\Admin\StationsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Cache::class],
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\AzuraCast\Radio\Configuration::class],
            $di[\App\Csrf::class],
            $config->get('forms/station'),
            $config->get('forms/station_clone')
        );
    };

    $di[Controller\Admin\UsersController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var Entity\Repository\BaseRepository $role_repo */
        $role_repo = $em->getRepository(\Entity\Role::class);

        return new Controller\Admin\UsersController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Auth::class],
            $di[\App\Csrf::class],
            $config->get('forms/user', [
                'roles' => $role_repo->fetchSelect()
            ])
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
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\AzuraCast\Sync\NowPlaying::class]
        );
    };

    $di[\Controller\Api\ListenersController::class] = function($di) {
        return new \Controller\Api\ListenersController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Cache::class]
        );
    };

    $di[\Controller\Api\Stations\MediaController::class] = function($di) {
        return new \Controller\Api\Stations\MediaController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Customization::class]
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

    $di[\Controller\Api\Stations\IndexController::class] = function($di) {
        return new \Controller\Api\Stations\IndexController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Radio\Adapters::class]
        );
    };

    $di[\Controller\Api\Stations\ServicesController::class] = function($di) {
        return new \Controller\Api\Stations\ServicesController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Radio\Configuration::class]
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

    $di[\Controller\Frontend\DashboardController::class] = function($di) {
        return new \Controller\Frontend\DashboardController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\App\Cache::class],
            $di[\InfluxDB\Database::class],
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\App\Url::class]
        );
    };

    $di[\Controller\Frontend\IndexController::class] = function($di) {
        return new \Controller\Frontend\IndexController(
            $di[\Entity\Repository\SettingsRepository::class]
        );
    };

    $di[\Controller\Frontend\ProfileController::class] = function($di) use ($app_settings) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Frontend\ProfileController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $config->get('forms/profile', [
                'settings' => $app_settings,
            ])
        );
    };

    $di[Controller\Frontend\ApiKeysController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new Controller\Frontend\ApiKeysController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->get('forms/api_key')
        );
    };

    $di[\Controller\Frontend\PublicController::class] = function($di) {
        return new \Controller\Frontend\PublicController();
    };

    $di[\Controller\Frontend\SetupController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Frontend\SetupController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Auth::class],
            $di[\AzuraCast\Acl\StationAcl::class],
            $di[\AzuraCast\Radio\Adapters::class],
            $di[\AzuraCast\Radio\Configuration::class],
            $config->get('forms/station'),
            $config->get('forms/settings')
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
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\AutomationController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\AzuraCast\Sync\RadioAutomation::class],
            $config->get('forms/automation')
        );
    };

    $di[\Controller\Stations\FilesController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\FilesController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Url::class],
            $di[\App\Csrf::class],
            $config->get('forms/media'),
            $config->get('forms/rename')
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
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\MountsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            [
                'icecast' => $config->get('forms/mount/icecast'),
                'remote' => $config->get('forms/mount/remote'),
                'shoutcast2' => $config->get('forms/mount/shoutcast2'),
            ]
        );
    };

    $di[\Controller\Stations\PlaylistsController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\PlaylistsController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Url::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->get('forms/playlist', [
                'customization' => $di[\AzuraCast\Customization::class]
            ])
        );
    };

    $di[\Controller\Stations\ProfileController::class] = function($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\ProfileController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Cache::class],
            $di[\AzuraCast\Radio\Configuration::class],
            $config->get('forms/station')
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
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\StreamersController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            $config->get('forms/streamer')
        );
    };

    $di[\Controller\Stations\WebhooksController::class] = function($di) use ($app_settings) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        return new \Controller\Stations\WebhooksController(
            $di[\Doctrine\ORM\EntityManager::class],
            $di[\App\Flash::class],
            $di[\App\Csrf::class],
            [
                'tunein' => $config->get('forms/webhook/tunein'),
                'discord' => $config->get('forms/webhook/discord', ['url' => $di[\App\Url::class], 'app_settings' => $app_settings]),
                'generic' => $config->get('forms/webhook/generic', ['url' => $di[\App\Url::class]]),
                'twitter' => $config->get('forms/webhook/twitter', ['url' => $di[\App\Url::class]]),
            ]
        );
    };

    return $di;

};