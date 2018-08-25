<?php
namespace App\Controller\Stations;

use Pimple\ServiceProviderInterface;
use Pimple\Container;

class StationsProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[AutomationController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new AutomationController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Sync\Task\RadioAutomation::class],
                $config->get('forms/automation')
            );
        };

        $di[Files\FilesController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Files\FilesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di['router'],
                $di[\App\Cache::class],
                $config->get('forms/rename')
            );
        };

        $di[Files\EditController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            $router = $di['router'];

            return new Files\EditController(
                $di[\Doctrine\ORM\EntityManager::class],
                $router,
                $di[\App\Cache::class],
                $config->get('forms/media', [
                    'router' => $router,
                ])
            );
        };

        $di[IndexController::class] = function($di) {
            return new IndexController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[MountsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new MountsController(
                $di[\Doctrine\ORM\EntityManager::class],
                [
                    'icecast' => $config->get('forms/mount/icecast'),
                    'remote' => $config->get('forms/mount/remote'),
                    'shoutcast2' => $config->get('forms/mount/shoutcast2'),
                ]
            );
        };

        $di[PlaylistsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new PlaylistsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di['router'],
                $config->get('forms/playlist', [
                    'customization' => $di[\App\Customization::class]
                ])
            );
        };

        $di[ProfileController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new ProfileController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\App\Radio\Configuration::class],
                $config->get('forms/station')
            );
        };

        $di[ReportsController::class] = function($di) {
            return new ReportsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Sync\Task\RadioAutomation::class]
            );
        };

        $di[RequestsController::class] = function($di) {
            return new RequestsController(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[StreamersController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new StreamersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/streamer')
            );
        };

        $di[WebhooksController::class] = function($di) {
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

            return new WebhooksController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Webhook\Dispatcher::class],
                $webhook_config,
                $webhook_forms
            );
        };
    }
}
