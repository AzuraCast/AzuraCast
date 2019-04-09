<?php
namespace App\Provider;

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
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\ApiController(
                $di[EntityManager::class],
                $config->get('forms/api_key')
            );
        };

        $di[Admin\BrandingController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\BrandingController(
                $di[Entity\Repository\SettingsRepository::class],
                $config->get('forms/branding', ['settings' => $di['settings']])
            );
        };

        $di[Admin\CustomFieldsController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\CustomFieldsController(
                $di[EntityManager::class],
                $config->get('forms/custom_field')
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
                $di[EntityManager::class],
                $di[\App\Form\PermissionsForm::class]
            );
        };

        $di[Admin\SettingsController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\SettingsController(
                $di[Entity\Repository\SettingsRepository::class],
                $config->get('forms/settings')
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
                $di[EntityManager::class],
                $di[\App\Auth::class],
                $di[\App\Form\UserForm::class]
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
