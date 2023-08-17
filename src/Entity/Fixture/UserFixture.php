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
            $demoUser->setEmail('demo@azuracast.com');
            $demoUser->setNewPassword('demo');
            $demoUser->setName('AzuraCast Demo User');

            /** @var Role $demoRole */
            $demoRole = $this->getReference('demo_role');
            $demoUser->getRoles()->add($demoRole);

            $manager->persist($demoUser);

            $this->addReference('demo_user', $demoUser);

            $adminUser = new User();
            $adminUser->setEmail($adminEmail);
            $adminUser->setName('System Administrator');
            $adminUser->setNewPassword($adminPassword);

            /** @var Role $adminRole */
            $adminRole = $this->getReference('admin_role');
            $adminUser->getRoles()->add($adminRole);

            $admin2faSecret = getenv('INIT_ADMIN_2FA_SECRET');
            if (!empty($admin2faSecret)) {
                $adminUser->setTwoFactorSecret($admin2faSecret);
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
