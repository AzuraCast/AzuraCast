<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class User extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $admin_email = getenv('INIT_ADMIN_EMAIL');
        $admin_password = getenv('INIT_ADMIN_PASSWORD');

        if (!empty($admin_email) && !empty($admin_password)) {
            $demo_user = new Entity\User();
            $demo_user->setEmail('demo@azuracast.com');
            $demo_user->setNewPassword('demo');
            $demo_user->setName('AzuraCast Demo User');
            $demo_user->getRoles()->add($this->getReference('demo_role'));
            $manager->persist($demo_user);

            $this->addReference('demo_user', $demo_user);

            $admin_user = new Entity\User();
            $admin_user->setEmail($admin_email);
            $admin_user->setName('System Administrator');
            $admin_user->setNewPassword($admin_password);
            $admin_user->setTheme('dark');

            $admin_user->getRoles()->add($this->getReference('admin_role'));

            $admin_2fa_secret = getenv('INIT_ADMIN_2FA_SECRET');
            if (!empty($admin_2fa_secret)) {
                $admin_user->setTwoFactorSecret($admin_2fa_secret);
            }

            $manager->persist($admin_user);

            $this->addReference('admin_user', $admin_user);
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
