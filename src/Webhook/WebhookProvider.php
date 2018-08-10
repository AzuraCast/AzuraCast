<?php
namespace App\Webhook;

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class WebhookProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Dispatcher::class] = function($di) {
            return new Dispatcher(
                $di[\Monolog\Logger::class],
                new \Pimple\Psr11\ServiceLocator($di, [
                    'discord'   => Connector\Discord::class,
                    'generic'   => Connector\Generic::class,
                    'local'     => Connector\Local::class,
                    'telegram'  => Connector\Telegram::class,
                    'tunein'    => Connector\TuneIn::class,
                    'twitter'   => Connector\Twitter::class,
                ])
            );
        };

        $di[Connector\Discord::class] = function($di) {
            return new Connector\Discord(
                $di[\Monolog\Logger::class]
            );
        };

        $di[Connector\Generic::class] = function($di) {
            return new Connector\Generic(
                $di[\Monolog\Logger::class]
            );
        };

        $di[Connector\Local::class] = function($di) {
            return new Connector\Local(
                $di[\Monolog\Logger::class],
                $di[\InfluxDB\Database::class],
                $di[\App\Cache::class],
                $di[\App\Entity\Repository\SettingsRepository::class]
            );
        };

        $di[Connector\TuneIn::class] = function($di) {
            return new Connector\TuneIn(
                $di[\Monolog\Logger::class]
            );
        };

        $di[Connector\Telegram::class] = function($di) {
            return new Connector\Telegram(
                $di[\Monolog\Logger::class]
            );
        };

        $di[Connector\Twitter::class] = function($di) {
            return new Connector\Twitter(
                $di[\Monolog\Logger::class]
            );
        };
    }
}
