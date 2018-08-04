<?php
namespace App\Controller\Admin;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use App\Entity;

class AdminProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[ApiController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new ApiController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/api_key')
            );
        };

        $di[BrandingController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new BrandingController(
                $di[Entity\Repository\SettingsRepository::class],
                $config->get('forms/branding', ['settings' => $di['app_settings']])
            );
        };

        $di[CustomFieldsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new CustomFieldsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $config->get('forms/custom_field')
            );
        };

        $di[IndexController::class] = function($di) {
            return new IndexController(
                $di[\App\Acl::class],
                $di[\App\Sync\Runner::class]
            );
        };

        $di[PermissionsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\StationRepository $stations_repo */
            $stations_repo = $em->getRepository(Entity\Station::class);

            $actions = $config->get('admin/actions');

            return new PermissionsController(
                $em,
                $actions,
                $config->get('forms/role', [
                    'actions' => $actions,
                    'all_stations' => $stations_repo->fetchArray(),
                ])
            );
        };

        $di[SettingsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new SettingsController(
                $di[Entity\Repository\SettingsRepository::class],
                $config->get('forms/settings')
            );
        };

        $di[StationsController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new StationsController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Cache::class],
                $di[\App\Radio\Adapters::class],
                $di[\App\Radio\Configuration::class],
                $config->get('forms/station'),
                $config->get('forms/station_clone')
            );
        };

        $di[UsersController::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\BaseRepository $role_repo */
            $role_repo = $em->getRepository(Entity\Role::class);

            return new UsersController(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\App\Auth::class],
                $config->get('forms/user', [
                    'roles' => $role_repo->fetchSelect()
                ])
            );
        };
    }
}
