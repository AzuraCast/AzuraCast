<?php
namespace App\Provider;

use App\Webhook;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class WebhookProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Webhook\Dispatcher::class] = function($di) {

            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            $webhooks = $config->get('webhooks');

            $services = [];
            foreach($webhooks['webhooks'] as $webhook_key => $webhook_info) {
                $services[$webhook_key] = $webhook_info['class'];
            }

            return new Webhook\Dispatcher(
                $di[\Monolog\Logger::class],
                new \Pimple\Psr11\ServiceLocator($di, $services)
            );
        };

        $di[Webhook\Connector\Discord::class] = function($di) {
            return new Webhook\Connector\Discord(
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class]
            );
        };

        $di[Webhook\Connector\Generic::class] = function($di) {
            return new Webhook\Connector\Generic(
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class]
            );
        };

        $di[Webhook\Connector\Local::class] = function($di) {
            return new Webhook\Connector\Local(
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class],
                $di[\InfluxDB\Database::class],
                $di[\Azura\Cache::class],
                $di[\App\Entity\Repository\SettingsRepository::class]
            );
        };

        $di[Webhook\Connector\TuneIn::class] = function($di) {
            return new Webhook\Connector\TuneIn(
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class]
            );
        };

        $di[Webhook\Connector\Telegram::class] = function($di) {
            return new Webhook\Connector\Telegram(
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class]
            );
        };

        $di[Webhook\Connector\Twitter::class] = function($di) {
            return new Webhook\Connector\Twitter(
                $di[\Monolog\Logger::class],
                $di[\GuzzleHttp\Client::class],
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };
    }
}
