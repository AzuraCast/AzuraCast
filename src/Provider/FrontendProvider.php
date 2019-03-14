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
                $di[\Azura\RateLimit::class],
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
                $di[\Azura\EventDispatcher::class]
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
                    'settings' => $di['settings'],
                ]),
                $config->get('forms/profile_two_factor')
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
                $config->get('forms/station'),
                $config->get('forms/settings')
            );
        };
    }
}
