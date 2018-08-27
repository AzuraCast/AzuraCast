<?php
namespace App\Provider;

use App\Controller\Api;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class ApiProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Api\IndexController::class] = function($di) {
            return new Api\IndexController;
        };

        $di[Api\InternalController::class] = function($di) {
            return new Api\InternalController(
                $di[\App\Acl::class],
                $di[\App\Sync\Task\NowPlaying::class]
            );
        };

        $di[Api\ListenersController::class] = function($di) {
            return new Api\ListenersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\MaxMind\Db\Reader::class]
            );
        };

        $di[Api\NowplayingController::class] = function($di) {
            return new Api\NowplayingController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class]
            );
        };

        $di[Api\RequestsController::class] = function($di) {
            return new Api\RequestsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\ApiUtilities::class]
            );
        };

        $di[Api\Stations\HistoryController::class] = function($di) {
            return new Api\Stations\HistoryController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\ApiUtilities::class]
            );
        };

        $di[Api\Stations\IndexController::class] = function($di) {
            return new Api\Stations\IndexController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Adapters::class]
            );
        };

        $di[Api\Stations\MediaController::class] = function($di) {
            return new Api\Stations\MediaController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Customization::class]
            );
        };

        $di[Api\Stations\ServicesController::class] = function($di) {
            return new Api\Stations\ServicesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Configuration::class]
            );
        };
    }
}
