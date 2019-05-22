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
                    // Every 15 seconds tasks
                    Task\NowPlaying::class,
                    Task\ReactivateStreamer::class,
                ]),
                new \Pimple\ServiceIterator($di, [
                    // Every minute tasks
                    Task\RadioRequests::class,
                ]),
                new \Pimple\ServiceIterator($di, [
                    // Every 5 minutes tasks
                    Task\Media::class,
                    Task\CheckForUpdates::class,
                ]),
                new \Pimple\ServiceIterator($di, [
                    // Every hour tasks
                    Task\Analytics::class,
                    Task\RadioAutomation::class,
                    Task\HistoryCleanup::class,
                    Task\RotateLogs::class,
                ])
            );
        };

        $di[Task\Analytics::class] = function($di) {
            return new Task\Analytics(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[Task\Backup::class] = function($di) {
            return new Task\Backup(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\App\MessageQueue::class],
                $di[\Azura\Console\Application::class]
            );
        };

        $di[Task\CheckForUpdates::class] = function($di) {
            return new Task\CheckForUpdates(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class],
                $di['settings'],
                $di[\App\Version::class]
            );
        };

        $di[Task\HistoryCleanup::class] = function($di) {
            return new Task\HistoryCleanup(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class]
            );
        };

        $di[Task\Media::class] = function($di) {
            return new Task\Media(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\App\Radio\Filesystem::class],
                $di[\App\MessageQueue::class]
            );
        };

        $di[Task\ReactivateStreamer::class] = function($di) {
            return new Task\ReactivateStreamer(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class]
            );
        };

        $di[Task\NowPlaying::class] = function($di) {
            return new Task\NowPlaying(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\App\Radio\Adapters::class],
                $di[\App\ApiUtilities::class],
                $di[\App\Radio\AutoDJ::class],
                $di[\Azura\Cache::class],
                $di[\InfluxDB\Database::class],
                $di[\Azura\EventDispatcher::class],
                $di[\App\MessageQueue::class]
            );
        };

        $di[Task\RadioAutomation::class] = function($di) {
            return new Task\RadioAutomation(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\App\Radio\Adapters::class]
            );
        };

        $di[Task\RadioRequests::class] = function($di) {
            return new Task\RadioRequests(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\App\Radio\Adapters::class],
                $di[\Azura\EventDispatcher::class]
            );
        };

        $di[Task\RotateLogs::class] = function($di) {
            return new Task\RotateLogs(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class],
                $di[\App\Radio\Adapters::class],
                $di[\Supervisor\Supervisor::class]
            );
        };
    }
}
