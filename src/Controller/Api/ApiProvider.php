<?php
namespace App\Controller\Api;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use App\Entity;

class ApiProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[IndexController::class] = function($di) {
            return new IndexController(
                $di[\App\Url::class]
            );
        };

        $di[InternalController::class] = function($di) {
            return new InternalController(
                $di[\App\Acl::class],
                $di[\App\Sync\Task\NowPlaying::class]
            );
        };

        $di[ListenersController::class] = function($di) {
            return new ListenersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\MaxMind\Db\Reader::class]
            );
        };

        $di[Stations\MediaController::class] = function($di) {
            return new Stations\MediaController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Customization::class]
            );
        };

        $di[NowplayingController::class] = function($di) {
            return new NowplayingController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class]
            );
        };

        $di[RequestsController::class] = function($di) {
            return new RequestsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Url::class],
                $di[\App\ApiUtilities::class]
            );
        };

        $di[Stations\IndexController::class] = function($di) {
            return new Stations\IndexController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Adapters::class]
            );
        };

        $di[Stations\ServicesController::class] = function($di) {
            return new Stations\ServicesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Configuration::class]
            );
        };
    }
}
