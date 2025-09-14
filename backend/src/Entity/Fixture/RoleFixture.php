<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Role;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

final class RoleFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $adminRole = new Role();
        $adminRole->name = 'Super Administrator';

        $demoRole = new Role();
        $demoRole->name = 'Demo Account';

        $manager->persist($adminRole);
        $manager->persist($demoRole);
        $manager->flush();

        $this->addReference('admin_role', $adminRole);
        $this->addReference('demo_role', $demoRole);
    }
}
