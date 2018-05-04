<?php
namespace Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Entity;

class StationMount extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em)
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');



        $em->flush();
    }

    public function getDependencies()
    {
        return [
            Station::class,
            StationPlaylist::class,
        ];
    }
}