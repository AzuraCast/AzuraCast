<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StationPlaylist extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var \App\Entity\Station $station */
        $station = $this->getReference('station');

        $playlist = new \App\Entity\StationPlaylist($station);
        $playlist->setName('default');
        $manager->persist($playlist);
        $manager->flush();

        $this->addReference('station_playlist', $playlist);
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
