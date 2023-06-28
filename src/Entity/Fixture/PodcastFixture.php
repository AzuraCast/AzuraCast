<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Podcast;
use App\Entity\PodcastCategory;
use App\Entity\Station;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class PodcastFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Station $station */
        $station = $this->getReference('station');

        $podcastStorage = $station->getPodcastsStorageLocation();

        $podcast = new Podcast($podcastStorage);

        $podcast->setTitle('The BoostTest Podcast');
        $podcast->setLink('https://demo.boost.mn');
        $podcast->setLanguage('en');
        $podcast->setDescription('The unofficial testing podcast for the BoostCast development team.');
        $podcast->setAuthor('BoostCast');
        $podcast->setEmail('demo@boost.mn');
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
            StationFixture::class,
        ];
    }
}
