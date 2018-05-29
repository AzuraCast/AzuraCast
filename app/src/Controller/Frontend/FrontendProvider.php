<?php
namespace Controller\Frontend;

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class FrontendProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[AccountController::class] = function($di) {
            return new AccountController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Auth::class],
                $di[\App\Session::class],
                $di[\App\Url::class],
                $di[\AzuraCast\RateLimit::class],
                $di[\AzuraCast\Acl\StationAcl::class]
            );
        };

        $di[DashboardController::class] = function($di) {
            return new DashboardController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\AzuraCast\Acl\StationAcl::class],
                $di[\App\Cache::class],
                $di[\InfluxDB\Database::class],
                $di[\AzuraCast\Radio\Adapters::class],
                $di[\App\Url::class]
            );
        };

        $di[IndexController::class] = function($di) {
            return new IndexController(
                $di[\Entity\Repository\SettingsRepository::class]
            );
        };

        $di[ProfileController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new ProfileController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $config->get('forms/profile', [
                    'settings' => $di['app_settings'],
                ])
            );
        };

        $di[ApiKeysController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new ApiKeysController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Csrf::class],
                $config->get('forms/api_key')
            );
        };

        $di[PublicController::class] = function($di) {
            return new PublicController();
        };

        $di[SetupController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new SetupController(
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
            $di[UtilController::class] = function ($di) {
                return new UtilController($di);
            };
        }
    }
}