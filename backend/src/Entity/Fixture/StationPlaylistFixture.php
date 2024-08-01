<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Station;
use App\Entity\StationPlaylist;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StationPlaylistFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Station $station */
        $station = $this->getReference('station');

        $playlist = new StationPlaylist($station);
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
            StationFixture::class,
        ];
    }
}
