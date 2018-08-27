<?php
namespace App\Provider;

use App\Sync;
use App\Sync\Task;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class SyncProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Sync\Runner::class] = function ($di) {
            return new Sync\Runner(
                $di[\App\Entity\Repository\SettingsRepository::class],
                $di[\Monolog\Logger::class],
                new \Pimple\ServiceIterator($di, [
                    Task\NowPlaying::class,
                ]),
                new \Pimple\ServiceIterator($di, [
                    Task\RadioRequests::class,
                ]),
                new \Pimple\ServiceIterator($di, [
                    Task\Media::class
                ]),
                new \Pimple\ServiceIterator($di, [
                    Task\Analytics::class,
                    Task\RadioAutomation::class,
                    Task\HistoryCleanup::class,
                ])
            );
        };

        $di[Task\Analytics::class] = function($di) {
            return new Task\Analytics(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[Task\HistoryCleanup::class] = function($di) {
            return new Task\HistoryCleanup(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Task\Media::class] = function($di) {
            return new Task\Media(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Task\NowPlaying::class] = function($di) {
            return new Task\NowPlaying(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\InfluxDB\Database::class],
                $di[\App\Cache::class],
                $di[\App\Radio\Adapters::class],
                $di[\App\Webhook\Dispatcher::class],
                $di[\App\ApiUtilities::class]
            );
        };

        $di[Task\RadioAutomation::class] = function($di) {
            return new Task\RadioAutomation(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Adapters::class]
            );
        };

        $di[Task\RadioRequests::class] = function($di) {
            return new Task\RadioRequests(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Adapters::class]
            );
        };
    }
}
