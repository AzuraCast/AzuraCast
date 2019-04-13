<?php
namespace App\Provider;

use App\Form;
use App\Entity;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Form\PermissionsForm::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[EntityManager::class];

            /** @var Entity\Repository\StationRepository $stations_repo */
            $stations_repo = $em->getRepository(Entity\Station::class);

            return new Form\PermissionsForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                $config->get('forms/role', [
                    'all_stations' => $stations_repo->fetchArray(),
                ])
            );
        };

        $di[Form\StationForm::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Form\StationForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                $di[\App\Acl::class],
                $config->get('forms/station')
            );
        };

        $di[Form\StationCloneForm::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Form\StationCloneForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                $di[\App\Acl::class],
                $di[\App\Radio\Configuration::class],
                $config->get('forms/station_clone')
            );
        };

        $di[Form\StationMountForm::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Form\StationMountForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                [],
                [],
                [
                    Adapters::FRONTEND_ICECAST => $config->get('forms/mount/icecast'),
                    Adapters::FRONTEND_SHOUTCAST => $config->get('forms/mount/shoutcast2'),
                ]
            );
        };

        $di[Form\StationPlaylistForm::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            return new Form\StationPlaylistForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                $config->get('forms/playlist', [
                    'customization' => $di[\App\Customization::class]
                ])
            );
        };

        $di[Form\UserForm::class] = function($di) {
            /** @var \Azura\Config $config */
            $config = $di[\Azura\Config::class];

            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[EntityManager::class];

            /** @var \Azura\Doctrine\Repository $role_repo */
            $role_repo = $em->getRepository(Entity\Role::class);

            return new Form\UserForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                $config->get('forms/user', [
                    'roles' => $role_repo->fetchSelect()
                ])
            );
        };

        $di[Form\EntityFormManager::class] = function($di) {
            $custom_forms = [
                Entity\Station::class   => Form\StationForm::class,
                Entity\User::class      => Form\UserForm::class,
                Entity\RolePermission::class => Form\PermissionsForm::class,
                Entity\StationPlaylist::class => Form\StationPlaylistForm::class,
                Entity\StationMount::class => Form\StationMountForm::class,
            ];

            return new Form\EntityFormManager(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class],
                new \Pimple\Psr11\ServiceLocator($di, $custom_forms)
            );
        };
    }
}
