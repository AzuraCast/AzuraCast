<?php
namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity;

class User extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em)
    {
        $demo_user = new Entity\User;
        $demo_user->setEmail('demo@azuracast.com');
        $demo_user->setAuthPassword('demo');
        $demo_user->setName('AzuraCast Demo User');
        $demo_user->getRoles()->add($this->getReference('demo_role'));
        $em->persist($demo_user);

        $admin_username = getenv('INIT_ADMIN_USERNAME');
        $admin_password = getenv('INIT_ADMIN_PASSWORD');

        if (!empty($admin_username) && !empty($admin_password)) {

            $admin_user = new Entity\User;
            $admin_user->setEmail($admin_username);
            $admin_user->setName('System Administrator');
            $admin_user->setAuthPassword($admin_password);
            $admin_user->setTheme('dark');

            $admin_user->getRoles()->add($this->getReference('admin_role'));

            $admin_2fa_secret = getenv('INIT_ADMIN_2FA_SECRET');
            if (!empty($admin_2fa_secret)) {
                $admin_user->setTwoFactorSecret($admin_2fa_secret);
            }

            $em->persist($admin_user);
        }

        $em->flush();
    }

    public function getDependencies()
    {
        return [
            Role::class,
        ];
    }
}
