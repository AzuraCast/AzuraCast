<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\PodcastCategory;

final class Podcast extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var \App\Entity\Station $station */
        $station = $this->getReference('station');

        $podcastStorage = $station->getPodcastsStorageLocation();

        $podcast = new \App\Entity\Podcast($podcastStorage);

        $podcast->setTitle('The AzuraTest Podcast');
        $podcast->setLink('https://demo.azuracast.com');
        $podcast->setLanguage('en');
        $podcast->setDescription('The unofficial testing podcast for the AzuraCast development team.');
        $podcast->setAuthor('AzuraCast');
        $podcast->setEmail('demo@azuracast.com');
        $manager->persist($podcast);

        $category = new PodcastCategory($podcast, 'Technology');
        $manager->persist($category);

        $manager->flush();

        $this->setReference('podcast', $podcast);
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            Station::class,
        ];
    }
}
