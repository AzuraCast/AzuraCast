<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use App\Enums\GlobalPermissions;
use App\Enums\StationPermissions;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RolePermission extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $permissions = [
            'admin_role' => [
                [GlobalPermissions::All, null],
            ],
            'demo_role' => [
                [StationPermissions::View, $station],
                [StationPermissions::Reports, $station],
                [StationPermissions::Profile, $station],
                [StationPermissions::Streamers, $station],
                [StationPermissions::MountPoints, $station],
                [StationPermissions::RemoteRelays, $station],
                [StationPermissions::Media, $station],
                [StationPermissions::Automation, $station],
                [StationPermissions::WebHooks, $station],
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
