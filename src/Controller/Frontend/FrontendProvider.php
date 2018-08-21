<?php
namespace App\Controller\Frontend;

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class FrontendProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[AccountController::class] = function($di) {
            return new AccountController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Auth::class],
                $di[\App\RateLimit::class],
                $di[\App\Acl::class]
            );
        };

        $di[DashboardController::class] = function($di) {
            return new DashboardController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Acl::class],
                $di[\App\Cache::class],
                $di[\InfluxDB\Database::class],
                $di[\App\Radio\Adapters::class],
                $di['router']
            );
        };

        $di[IndexController::class] = function($di) {
            return new IndexController(
                $di[\App\Entity\Repository\SettingsRepository::class]
            );
        };

        $di[ProfileController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new ProfileController(
                $di[\Doctrine\ORM\EntityManager::class],
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
                $di[\App\Auth::class],
                $di[\App\Acl::class],
                $di[\App\Radio\Adapters::class],
                $di[\App\Radio\Configuration::class],
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
