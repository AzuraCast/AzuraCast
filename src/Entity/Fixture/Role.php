<?php

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class Role extends AbstractFixture
{
    public function load(ObjectManager $em): void
    {
        $admin_role = new Entity\Role();
        $admin_role->setName('Super Administrator');

        $demo_role = new Entity\Role();
        $demo_role->setName('Demo Account');

        $em->persist($admin_role);
        $em->persist($demo_role);
        $em->flush();

        $this->addReference('admin_role', $admin_role);
        $this->addReference('demo_role', $demo_role);
    }
}
