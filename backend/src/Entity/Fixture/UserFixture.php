<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $adminEmail = getenv('INIT_ADMIN_EMAIL');
        $adminPassword = getenv('INIT_ADMIN_PASSWORD');

        if (!empty($adminEmail) && !empty($adminPassword)) {
            $demoUser = new User();
            $demoUser->email = 'demo@azuracast.com';
            $demoUser->setNewPassword('demo');
            $demoUser->name = 'AzuraCast Demo User';

            $demoRole = $this->getReference('demo_role', Role::class);
            $demoUser->roles->add($demoRole);

            $manager->persist($demoUser);

            $this->addReference('demo_user', $demoUser);

            $adminUser = new User();
            $adminUser->email = $adminEmail;
            $adminUser->name = 'System Administrator';
            $adminUser->setNewPassword($adminPassword);

            $adminRole = $this->getReference('admin_role', Role::class);
            $adminUser->roles->add($adminRole);

            $admin2faSecret = getenv('INIT_ADMIN_2FA_SECRET');
            if (!empty($admin2faSecret)) {
                $adminUser->two_factor_secret = $admin2faSecret;
            }

            $manager->persist($adminUser);

            $this->addReference('admin_user', $adminUser);
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
