<?php
namespace App\Provider;

use App\Controller\Frontend;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class FrontendProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Frontend\AccountController::class] = function($di) {
            return new Frontend\AccountController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Auth::class],
                $di[\App\RateLimit::class],
                $di[\App\Acl::class]
            );
        };

        $di[Frontend\DashboardController::class] = function($di) {
            return new Frontend\DashboardController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Acl::class],
                $di[\Azura\Cache::class],
                $di[\InfluxDB\Database::class],
                $di[\App\Radio\Adapters::class],
                $di['router']
            );
        };

        $di[Frontend\IndexController::class] = function($di) {
            return new Frontend\IndexController(
                $di[\App\Entity\Repository\SettingsRepository::class]
            );
        };

        $di[Frontend\ProfileController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Frontend\ProfileController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/profile', [
                    'settings' => $di['app_settings'],
                ])
            );
        };

        $di[Frontend\ApiKeysController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Frontend\ApiKeysController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/api_key')
            );
        };

        $di[Frontend\PublicController::class] = function($di) {
            return new Frontend\PublicController();
        };

        $di[Frontend\SetupController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Frontend\SetupController(
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
            $di[Frontend\UtilController::class] = function ($di) {
                return new Frontend\UtilController($di);
            };
        }
    }
}
