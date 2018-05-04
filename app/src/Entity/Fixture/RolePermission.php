<?php
namespace Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Entity;

class RolePermission extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em)
    {
        $permissions = [
            'admin_role' => [
                'administer all',
            ],
            'demo_role' => [
                'administer api keys',
                'administer stations',
                'view administration',
            ],
        ];

        foreach($permissions as $role_reference => $perm_names) {
            /** @var Entity\Role $role */
            $role = $this->getReference($role_reference);

            foreach($perm_names as $perm_name) {
                $rp = new Entity\RolePermission($role);
                $rp->setActionName($perm_name);
                $em->persist($rp);
            }
        }

        $em->flush();
    }

    public function getDependencies()
    {
        return [
            Role::class
        ];
    }
}