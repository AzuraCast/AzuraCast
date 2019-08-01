<?php
namespace App\Provider;

use App;
use Azura;
use App\Controller\Admin;
use Doctrine\ORM\EntityManager;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use App\Entity;

class AdminProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Admin\ApiController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            /** @var App\Form\EntityFormManager $form_manager */
            $form_manager = $di[App\Form\EntityFormManager::class];

            return new Admin\ApiController(
                $form_manager->getForm(App\Entity\ApiKey::class, $config->get('forms/api_key'))
            );
        };

        $di[Admin\BackupsController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            $settings_form = new App\Form\SettingsForm(
                $di[EntityManager::class],
                $config->get('forms/backup')
            );

            $backup_run_form = new App\Form\Form(
                $config->get('forms/backup_run')
            );

            return new Admin\BackupsController(
                $settings_form,
                $backup_run_form,
                $di[App\Sync\Task\Backup::class]
            );
        };

        $di[Admin\BrandingController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            $form_config = $config->get('forms/branding', ['settings' => $di['settings']]);

            return new Admin\BrandingController(
                new App\Form\SettingsForm($di[EntityManager::class], $form_config)
            );
        };

        $di[Admin\CustomFieldsController::class] = function($di) {
            /** @var Azura\Config $config */
            $config = $di[Azura\Config::class];

            /** @var App\Form\EntityFormManager $form_manager */
            $form_manager = $di[App\Form\EntityFormManager::class];

            return new Admin\CustomFieldsController(
                $form_manager->getForm(App\Entity\CustomField::class, $config->get('forms/custom_field'))
            );
        };

        $di[Admin\IndexController::class] = function($di) {
            return new Admin\IndexController(
                $di[\App\Acl::class],
                $di[\Monolog\Logger::class],
                $di[\App\Sync\Runner::class]
            );
        };

        $di[Admin\LogsController::class] = function($di) {
            return new Admin\LogsController(
                $di[EntityManager::class]
            );
        };

        $di[Admin\PermissionsController::class] = function($di) {
            return new Admin\PermissionsController(
                $di[\App\Form\PermissionsForm::class]
            );
        };

        $di[Admin\RelaysController::class] = function($di) {
            return new Admin\RelaysController(
                $di[EntityManager::class]
            );
        };

        $di[Admin\SettingsController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\SettingsController(
                new App\Form\SettingsForm($di[EntityManager::class], $config->get('forms/settings'))
            );
        };

        $di[Admin\StationsController::class] = function($di) {
            return new Admin\StationsController(
                $di[\App\Form\StationForm::class],
                $di[\App\Form\StationCloneForm::class]
            );
        };

        $di[Admin\UsersController::class] = function($di) {
            return new Admin\UsersController(
                $di[\App\Form\UserForm::class],
                $di[\App\Auth::class]
            );
        };

        $di[Admin\InstallShoutcastController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\InstallShoutcastController(
                $config->get('forms/install_shoutcast')
            );
        };
    }
}
