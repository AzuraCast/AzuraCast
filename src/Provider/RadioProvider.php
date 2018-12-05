<?php
namespace App\Provider;

use App\Radio;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class RadioProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Radio\Adapters::class] = function($di) {
            return new Radio\Adapters(new \Pimple\Psr11\ServiceLocator($di, [
                Radio\Backend\Liquidsoap::class,
                Radio\Backend\None::class,
                Radio\Frontend\Icecast::class,
                Radio\Frontend\Remote::class,
                Radio\Frontend\SHOUTcast::class,
                Radio\Remote\Icecast::class,
                Radio\Remote\SHOUTcast1::class,
                Radio\Remote\SHOUTcast2::class,
            ]));
        };

        $di[Radio\AutoDJ::class] = function($di) {
            return new Radio\AutoDJ(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Azura\EventDispatcher::class]
            );
        };

        $di[Radio\Configuration::class] = function($di) {
            return new Radio\Configuration(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[Radio\Adapters::class],
                $di[\Supervisor\Supervisor::class]
            );
        };

        $di[Radio\Backend\Liquidsoap::class] = function($di) {
            return new Radio\Backend\Liquidsoap(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\Azura\EventDispatcher::class],
                $di[Radio\AutoDJ::class],
                $di[Radio\Filesystem::class]
            );
        };

        $di[Radio\Backend\None::class] = function($di) {
            return new Radio\Backend\None(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\Azura\EventDispatcher::class]
            );
        };

        $di[Radio\Filesystem::class] = function($di) {
            /** @var \Redis $redis */
            $redis = $di[\Redis::class];
            $redis->select(5);

            return new Radio\Filesystem($redis);
        };

        $di[Radio\Frontend\Icecast::class] = function($di) {
            return new Radio\Frontend\Icecast(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\Azura\EventDispatcher::class],
                $di[\GuzzleHttp\Client::class],
                $di['router']
            );
        };

        $di[Radio\Frontend\Remote::class] = function($di) {
            return new Radio\Frontend\Remote(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\Azura\EventDispatcher::class],
                $di[\GuzzleHttp\Client::class],
                $di['router']
            );
        };

        $di[Radio\Frontend\SHOUTcast::class] = function($di) {
            return new Radio\Frontend\SHOUTcast(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\Azura\EventDispatcher::class],
                $di[\GuzzleHttp\Client::class],
                $di['router']
            );
        };

        $di[Radio\Remote\Icecast::class] = function($di) {
            return new Radio\Remote\Icecast(
                $di[\GuzzleHttp\Client::class],
                $di[\Monolog\Logger::class]
            );
        };

        $di[Radio\Remote\SHOUTcast1::class] = function($di) {
            return new Radio\Remote\SHOUTcast1(
                $di[\GuzzleHttp\Client::class],
                $di[\Monolog\Logger::class]
            );
        };

        $di[Radio\Remote\SHOUTcast2::class] = function($di) {
            return new Radio\Remote\SHOUTcast2(
                $di[\GuzzleHttp\Client::class],
                $di[\Monolog\Logger::class]
            );
        };
    }
}
