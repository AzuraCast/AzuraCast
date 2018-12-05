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
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\AutomationController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Sync\Task\RadioAutomation::class],
                $config->get('forms/automation')
            );
        };

        $di[Stations\Files\BatchController::class] = function($di) {
            return new Stations\Files\BatchController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Filesystem::class]
            );
        };

        $di[Stations\Files\FilesController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\Files\FilesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Filesystem::class],
                $config->get('forms/rename')
            );
        };

        $di[Stations\Files\ListController::class] = function($di) {
            return new Stations\Files\ListController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Filesystem::class]
            );
        };

        $di[Stations\Files\EditController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\Files\EditController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Filesystem::class],
                $config->get('forms/media', [
                    'router' => $di['router'],
                ])
            );
        };

        $di[Stations\Profile\IndexController::class] = function($di) {
            return new Stations\Profile\IndexController(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Stations\Profile\EditController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\Profile\EditController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Azura\Cache::class],
                $di[\App\Radio\Configuration::class],
                $config->get('forms/station')
            );
        };

        $di[Stations\Reports\OverviewController::class] = function($di) {
            return new Stations\Reports\OverviewController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[Stations\MountsController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\MountsController(
                $di[\Doctrine\ORM\EntityManager::class],
                [
                    'icecast' => $config->get('forms/mount/icecast'),
                    'shoutcast2' => $config->get('forms/mount/shoutcast2'),
                ]
            );
        };

        $di[Stations\PlaylistsController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\PlaylistsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di['router'],
                $config->get('forms/playlist', [
                    'customization' => $di[\App\Customization::class]
                ])
            );
        };

        $di[Stations\RemotesController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\RemotesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/remote')
            );
        };

        $di[Stations\Reports\DuplicatesController::class] = function($di) {
            return new Stations\Reports\DuplicatesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Radio\Filesystem::class]
            );
        };

        $di[Stations\Reports\ListenersController::class] = function($di) {
            return new Stations\Reports\ListenersController(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Stations\Reports\PerformanceController::class] = function($di) {
            return new Stations\Reports\PerformanceController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Sync\Task\RadioAutomation::class]
            );
        };

        $di[Stations\Reports\RequestsController::class] = function($di) {
            return new Stations\Reports\RequestsController(
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Stations\Reports\SoundExchangeController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\Reports\SoundExchangeController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\GuzzleHttp\Client::class],
                $config->get('forms/report/soundexchange')
            );
        };

        $di[Stations\Reports\TimelineController::class] = function($di) {
            return new Stations\Reports\TimelineController();
        };

        $di[Stations\StreamersController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Stations\StreamersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/streamer')
            );
        };

        $di[Stations\WebhooksController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            $webhook_config = $config->get('webhooks');

            $webhook_forms = [];
            $config_injections = [
                'router' => $di['router'],
                'app_settings' => $di['settings'],
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
