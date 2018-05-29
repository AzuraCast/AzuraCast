<?php
namespace Controller\Api;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Entity;

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
                $di[\AzuraCast\Acl\StationAcl::class],
                $di[\AzuraCast\Radio\Adapters::class],
                $di[\AzuraCast\Sync\Task\NowPlaying::class]
            );
        };

        $di[ListenersController::class] = function($di) {
            return new ListenersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class]
            );
        };

        $di[Stations\MediaController::class] = function($di) {
            return new Stations\MediaController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\AzuraCast\Customization::class]
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
                $di[\AzuraCast\Radio\Adapters::class],
                $di[\App\Url::class],
                $di[\AzuraCast\ApiUtilities::class]
            );
        };

        $di[Stations\IndexController::class] = function($di) {
            return new Stations\IndexController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\AzuraCast\Radio\Adapters::class]
            );
        };

        $di[Stations\ServicesController::class] = function($di) {
            return new Stations\ServicesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\AzuraCast\Radio\Configuration::class]
            );
        };
    }
}