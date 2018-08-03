<?php
namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity;

class StationPlaylist extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em)
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $playlist = new Entity\StationPlaylist($station);
        $playlist->setName('default');
        $em->persist($playlist);
        $em->flush();

        $this->addReference('station_playlist', $playlist);
    }

    public function getDependencies()
    {
        return [
            Station::class,
        ];
    }
}
