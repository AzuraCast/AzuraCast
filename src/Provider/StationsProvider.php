<?php
namespace App\Provider;

use App;
use Azura;
use App\Controller\Stations;
use Doctrine\ORM\EntityManager;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class StationsProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Stations\AutomationController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            return new Stations\AutomationController(
                $di[EntityManager::class],
                $di[App\Sync\Task\RadioAutomation::class],
                $config->get('forms/automation')
            );
        };

        $di[Stations\Files\BatchController::class] = function($di) {
            return new Stations\Files\BatchController(
                $di[EntityManager::class],
                $di[App\Radio\Filesystem::class]
            );
        };

        $di[Stations\Files\FilesController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            return new Stations\Files\FilesController(
                $di[EntityManager::class],
                $di[App\Radio\Filesystem::class],
                $config->get('forms/rename')
            );
        };

        $di[Stations\Files\ListController::class] = function($di) {
            return new Stations\Files\ListController(
                $di[EntityManager::class],
                $di[App\Radio\Filesystem::class]
            );
        };

        $di[Stations\Files\EditController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            return new Stations\Files\EditController(
                $di[EntityManager::class],
                $di[App\Radio\Filesystem::class],
                $config->get('forms/media', [
                    'router' => $di['router'],
                ])
            );
        };

        $di[Stations\ProfileController::class] = function($di) {
            return new Stations\ProfileController(
                $di[EntityManager::class],
                $di[App\Form\StationForm::class]
            );
        };

        $di[Stations\Reports\OverviewController::class] = function($di) {
            return new Stations\Reports\OverviewController(
                $di[EntityManager::class],
                $di[\InfluxDB\Database::class]
            );
        };

        $di[Stations\MountsController::class] = function($di) {
            return new Stations\MountsController($di[App\Form\StationMountForm::class]);
        };

        $di[Stations\PlaylistsController::class] = function($di) {
            return new Stations\PlaylistsController($di[App\Form\StationPlaylistForm::class]);
        };

        $di[Stations\RemotesController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            /** @var App\Form\EntityFormManager $form_manager */
            $form_manager = $di[App\Form\EntityFormManager::class];

            return new Stations\RemotesController(
                $form_manager->getForm(App\Entity\StationRemote::class, $config->get('forms/remote'))
            );
        };

        $di[Stations\Reports\DuplicatesController::class] = function($di) {
            return new Stations\Reports\DuplicatesController(
                $di[EntityManager::class],
                $di[App\Radio\Filesystem::class]
            );
        };

        $di[Stations\Reports\ListenersController::class] = function($di) {
            return new Stations\Reports\ListenersController(
                $di[EntityManager::class]
            );
        };

        $di[Stations\Reports\PerformanceController::class] = function($di) {
            return new Stations\Reports\PerformanceController(
                $di[EntityManager::class],
                $di[App\Sync\Task\RadioAutomation::class]
            );
        };

        $di[Stations\Reports\RequestsController::class] = function($di) {
            return new Stations\Reports\RequestsController(
                $di[EntityManager::class]
            );
        };

        $di[Stations\Reports\SoundExchangeController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            return new Stations\Reports\SoundExchangeController(
                $di[EntityManager::class],
                $di[\GuzzleHttp\Client::class],
                $config->get('forms/report/soundexchange')
            );
        };

        $di[Stations\Reports\TimelineController::class] = function($di) {
            return new Stations\Reports\TimelineController();
        };

        $di[Stations\StreamersController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];
            
            /** @var App\Form\EntityFormManager $form_manager */
            $form_manager = $di[App\Form\EntityFormManager::class];

            return new Stations\StreamersController(
                $form_manager->getForm(App\Entity\StationStreamer::class, $config->get('forms/streamer'))
            );
        };

        $di[Stations\WebhooksController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

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
                $di[EntityManager::class],
                $di[App\Webhook\Dispatcher::class],
                $webhook_config,
                $webhook_forms
            );
        };
    }
}
