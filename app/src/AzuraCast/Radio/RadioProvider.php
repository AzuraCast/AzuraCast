<?php
namespace AzuraCast\Radio;

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
            ]));
        };

        $di[Configuration::class] = function($di) {
            return new Configuration(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[Adapters::class],
                $di[\Supervisor\Supervisor::class]
            );
        };

        $di[Backend\Liquidsoap::class] = $di->factory(function($di) {
            return new Backend\Liquidsoap(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class]
            );
        });

        $di[Backend\None::class] = $di->factory(function($di) {
            return new Backend\None(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class]
            );
        });

        $di[Frontend\Icecast::class] = $di->factory(function($di) {
            return new Frontend\Icecast(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\Url::class]
            );
        });

        $di[Frontend\Remote::class] = $di->factory(function($di) {
            return new Frontend\Remote(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\Url::class]
            );
        });

        $di[Frontend\SHOUTcast::class] = $di->factory(function($di) {
            return new Frontend\SHOUTcast(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Supervisor\Supervisor::class],
                $di[\Monolog\Logger::class],
                $di[\App\Url::class]
            );
        });
    }
}