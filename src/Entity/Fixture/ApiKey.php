<?php

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ApiKey extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em): void
    {
        $demo_api_key = getenv('INIT_DEMO_API_KEY');

        if (!empty($demo_api_key) && $this->hasReference('demo_user')) {
            /** @var Entity\User $demo_user */
            $demo_user = $this->getReference('demo_user');

            $api_key = new Entity\ApiKey($demo_user, $demo_api_key);
            $api_key->setComment('Demo User');

            $em->persist($api_key);
        }

        $admin_api_key = getenv('INIT_ADMIN_API_KEY');

        if (!empty($admin_api_key) && $this->hasReference('admin_user')) {
            /** @var Entity\User $admin_user */
            $admin_user = $this->getReference('admin_user');

            $api_key = new Entity\ApiKey($admin_user, $admin_api_key);
            $api_key->setComment('Administrator');

            $em->persist($api_key);
        }

        $em->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            User::class,
        ];
    }
}
