<?php
namespace Controller\Stations;

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
                $di[\App\Flash::class],
                $di[\AzuraCast\Sync\Task\RadioAutomation::class],
                $config->get('forms/automation')
            );
        };

        $di[Files\FilesController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Files\FilesController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Url::class],
                $di[\App\Csrf::class],
                $config->get('forms/rename')
            );
        };

        $di[Files\EditController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            $url = $di[\App\Url::class];

            return new Files\EditController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $url,
                $di[\App\Csrf::class],
                $config->get('forms/media', [
                    'url' => $url
                ])
            );
        };

        $di[IndexController::class] = function($di) {
            return new IndexController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[MountsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new MountsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Csrf::class],
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
                $di[\App\Url::class],
                $di[\App\Flash::class],
                $di[\App\Csrf::class],
                $config->get('forms/playlist', [
                    'customization' => $di[\AzuraCast\Customization::class]
                ])
            );
        };

        $di[ProfileController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new ProfileController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Cache::class],
                $di[\AzuraCast\Radio\Configuration::class],
                $config->get('forms/station')
            );
        };

        $di[ReportsController::class] = function($di) {
            return new ReportsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\App\Flash::class],
                $di[\AzuraCast\Sync\Task\RadioAutomation::class]
            );
        };

        $di[RequestsController::class] = function($di) {
            return new RequestsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Csrf::class]
            );
        };

        $di[StreamersController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new StreamersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Csrf::class],
                $config->get('forms/streamer')
            );
        };

        $di[WebhooksController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new WebhooksController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Flash::class],
                $di[\App\Csrf::class],
                [
                    'tunein' => $config->get('forms/webhook/tunein'),
                    'discord' => $config->get('forms/webhook/discord', ['url' => $di[\App\Url::class], 'app_settings' => $di['app_settings']]),
                    'generic' => $config->get('forms/webhook/generic', ['url' => $di[\App\Url::class]]),
                    'twitter' => $config->get('forms/webhook/twitter', ['url' => $di[\App\Url::class]]),
                ]
            );
        };
    }
}