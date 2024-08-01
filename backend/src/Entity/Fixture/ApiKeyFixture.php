<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Security\SplitToken;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ApiKeyFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $demoApiKey = getenv('INIT_DEMO_API_KEY');

        if (!empty($demoApiKey) && $this->hasReference('demo_user')) {
            /** @var User $demoUser */
            $demoUser = $this->getReference('demo_user');

            $apiKey = new ApiKey($demoUser, SplitToken::fromKeyString($demoApiKey));
            $apiKey->setComment('Demo User');

            $manager->persist($apiKey);
        }

        $adminApiKey = getenv('INIT_ADMIN_API_KEY');

        if (!empty($adminApiKey) && $this->hasReference('admin_user')) {
            /** @var User $adminUser */
            $adminUser = $this->getReference('admin_user');

            $apiKey = new ApiKey($adminUser, SplitToken::fromKeyString($adminApiKey));
            $apiKey->setComment('Administrator');

            $manager->persist($apiKey);
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
