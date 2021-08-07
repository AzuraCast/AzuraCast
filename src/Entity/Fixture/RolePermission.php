<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Acl;
use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RolePermission extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $permissions = [
            'admin_role' => [
                [Acl::GLOBAL_ALL, null],
            ],
            'demo_role' => [
                [Acl::STATION_VIEW, $station],
                [Acl::STATION_REPORTS, $station],
                [Acl::STATION_PROFILE, $station],
                [Acl::STATION_STREAMERS, $station],
                [Acl::STATION_MOUNTS, $station],
                [Acl::STATION_REMOTES, $station],
                [Acl::STATION_MEDIA, $station],
                [Acl::STATION_AUTOMATION, $station],
                [Acl::STATION_WEB_HOOKS, $station],
            ],
        ];

        foreach ($permissions as $role_reference => $perm_names) {
            /** @var Entity\Role $role */
            $role = $this->getReference($role_reference);

            foreach ($perm_names as $perm_name) {
                $rp = new Entity\RolePermission($role, $perm_name[1], $perm_name[0]);
                $manager->persist($rp);
            }
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            Role::class,
        ];
    }
}
