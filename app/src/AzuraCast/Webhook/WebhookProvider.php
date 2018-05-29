<?php
namespace AzuraCast\Webhook;

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
                    'local'     => Connector\Local::class,
                    'generic'   => Connector\Generic::class,
                    'tunein'    => Connector\TuneIn::class,
                    'discord'   => Connector\Discord::class,
                    'twitter'   => Connector\Twitter::class,
                ])
            );
        };

        $di[Connector\Local::class] = function($di) {
            return new Connector\Local(
                $di[\Monolog\Logger::class],
                $di[\InfluxDB\Database::class],
                $di[\App\Cache::class],
                $di[\Entity\Repository\SettingsRepository::class]
            );
        };

        $di[Connector\Generic::class] = function($di) {
            return new Connector\Generic(
                $di[\Monolog\Logger::class]
            );
        };

        $di[Connector\TuneIn::class] = function($di) {
            return new Connector\TuneIn(
                $di[\Monolog\Logger::class]
            );
        };

        $di[Connector\Discord::class] = function($di) {
            return new Connector\Discord(
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