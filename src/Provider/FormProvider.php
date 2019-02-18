<?php
namespace App\Provider;

use App\Form;
use App\Entity;
use Doctrine\ORM\EntityManager;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Form\EntityForm::class] = function($di) {
            return new Form\EntityForm(
                $di[EntityManager::class],
                $di[Serializer::class],
                $di[ValidatorInterface::class]
            );
        };

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


    }
}