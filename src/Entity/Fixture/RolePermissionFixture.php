<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Station;
use App\Enums\GlobalPermissions;
use App\Enums\StationPermissions;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RolePermissionFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Station $station */
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
            ],
        ];

        foreach ($permissions as $roleReference => $permNames) {
            /** @var Role $role */
            $role = $this->getReference($roleReference);

            foreach ($permNames as $permName) {
                $rp = new RolePermission($role, $permName[1], $permName[0]);
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
            RoleFixture::class,
        ];
    }
}
