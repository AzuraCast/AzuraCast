<?php
namespace App\Provider;

use App\Controller\Stations;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class StationsProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Stations\AutomationController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\AutomationController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Sync\Task\RadioAutomation::class],
                $config->get('forms/automation')
            );
        };

        $di[Stations\Files\FilesController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\Files\FilesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di['router'],
                $di[\App\Cache::class],
                $di[\App\Radio\AutoDJ::class],
                $config->get('forms/rename')
            );
        };

        $di[Stations\Files\EditController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            $router = $di['router'];

            return new Stations\Files\EditController(
                $di[\Doctrine\ORM\EntityManager::class],
                $router,
                $di[\App\Cache::class],
                $di[\App\Radio\AutoDJ::class],
                $config->get('forms/media', [
                    'router' => $router,
                ])
            );
        };

        $di[Stations\IndexController::class] = function($di) {
            return new Stations\IndexController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[Stations\MountsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\MountsController(
                $di[\Doctrine\ORM\EntityManager::class],
                [
                    'icecast' => $config->get('forms/mount/icecast'),
                    'shoutcast2' => $config->get('forms/mount/shoutcast2'),
                ]
            );
        };

        $di[Stations\PlaylistsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\PlaylistsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di['router'],
                $di[\App\Radio\AutoDJ::class],
                $config->get('forms/playlist', [
                    'customization' => $di[\App\Customization::class]
                ])
            );
        };

        $di[Stations\ProfileController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\ProfileController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\App\Radio\Configuration::class],
                $config->get('forms/station')
            );
        };

        $di[Stations\RemotesController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\RemotesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/remote')
            );
        };

        $di[Stations\ReportsController::class] = function($di) {
            return new Stations\ReportsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Sync\Task\RadioAutomation::class]
            );
        };

        $di[Stations\RequestsController::class] = function($di) {
            return new Stations\RequestsController(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Stations\StreamersController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Stations\StreamersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/streamer')
            );
        };

        $di[Stations\WebhooksController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            $webhook_config = $config->get('webhooks');

            $webhook_forms = [];
            $config_injections = [
                'router' => $di['router'],
                'app_settings' => $di['app_settings'],
                'triggers' => $webhook_config['triggers'],
            ];
            foreach($webhook_config['webhooks'] as $webhook_key => $webhook_info) {
                $webhook_forms[$webhook_key] = $config->get('forms/webhook/'.$webhook_key, $config_injections);
            }

            return new Stations\WebhooksController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Webhook\Dispatcher::class],
                $webhook_config,
                $webhook_forms
            );
        };
    }
}
