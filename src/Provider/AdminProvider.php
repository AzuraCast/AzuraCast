<?php
namespace App\Provider;

use App\Controller\Admin;
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
                $di[\Doctrine\ORM\EntityManager::class],
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
                $di[\Doctrine\ORM\EntityManager::class],
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
                $di[\Doctrine\ORM\EntityManager::class]
            );
        };

        $di[Admin\PermissionsController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\StationRepository $stations_repo */
            $stations_repo = $em->getRepository(Entity\Station::class);

            $actions = $config->get('admin/actions');

            return new Admin\PermissionsController(
                $em,
                $actions,
                $config->get('forms/role', [
                    'actions' => $actions,
                    'all_stations' => $stations_repo->fetchArray(),
                ])
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
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\StationsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Azura\Cache::class],
                $di[\App\Radio\Adapters::class],
                $di[\App\Radio\Configuration::class],
                $config->get('forms/station'),
                $config->get('forms/station_clone')
            );
        };

        $di[Admin\UsersController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var \Azura\Doctrine\Repository $role_repo */
            $role_repo = $em->getRepository(Entity\Role::class);

            return new Admin\UsersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Auth::class],
                $config->get('forms/user', [
                    'roles' => $role_repo->fetchSelect()
                ])
            );
        };

        $di[Admin\Install\ShoutcastController::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Admin\Install\ShoutcastController(
                $config->get('forms/install_shoutcast')
            );
        };
    }
}
