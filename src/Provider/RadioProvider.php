<?php
namespace App\Provider;

use App\Radio\Adapters;
use App\Radio\AutoDJ;
use App\Radio\Backend;
use App\Radio\Configuration;
use App\Radio\Frontend;
use App\Radio\Remote;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class RadioProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Adapters::class] = function($di) {
            return new Adapters(new \Pimple\Psr11\ServiceLocator($di, [
                Backend\Liquidsoap::class,
                Backend\None::class,
                Frontend\Icecast::class,
                Frontend\Remote::class,
                Frontend\SHOUTcast::class,
                Remote\Icecast::class,
                Remote\SHOUTcast1::class,
                Remote\SHOUTcast2::class,
            ]));
        };

        $di[AutoDJ::class] = function($di) {
            return new AutoDJ(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\App\EventDispatcher::class]
            );
        };

        $di[Configuration::class] = function($di) {
            return new Configuration(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[Adapters::class],
                $di[\Supervisor\Supervisor::class]
            );
        };

        $di[Backend\Liquidsoap::class] = function($di) {
            return new Backend\Liquidsoap(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\EventDispatcher::class],
                $di[AutoDJ::class]
            );
        };

        $di[Backend\None::class] = function($di) {
            return new Backend\None(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\EventDispatcher::class]
            );
        };

        $di[Frontend\Icecast::class] = function($di) {
            return new Frontend\Icecast(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\EventDispatcher::class],
                $di[\GuzzleHttp\Client::class],
                $di['router']
            );
        };

        $di[Frontend\Remote::class] = function($di) {
            return new Frontend\Remote(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\EventDispatcher::class],
                $di[\GuzzleHttp\Client::class],
                $di['router']
            );
        };

        $di[Frontend\SHOUTcast::class] = function($di) {
            return new Frontend\SHOUTcast(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\EventDispatcher::class],
                $di[\GuzzleHttp\Client::class],
                $di['router']
            );
        };

        $di[Remote\Icecast::class] = function($di) {
            return new Remote\Icecast(
                $di[\GuzzleHttp\Client::class],
                $di[\Monolog\Logger::class]
            );
        };

        $di[Remote\SHOUTcast1::class] = function($di) {
            return new Remote\SHOUTcast1(
                $di[\GuzzleHttp\Client::class],
                $di[\Monolog\Logger::class]
            );
        };

        $di[Remote\SHOUTcast2::class] = function($di) {
            return new Remote\SHOUTcast2(
                $di[\GuzzleHttp\Client::class],
                $di[\Monolog\Logger::class]
            );
        };
    }
}
