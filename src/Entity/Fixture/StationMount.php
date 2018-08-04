<?php
namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity;

class StationMount extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em)
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $mount_radio = new Entity\StationMount($station);
        $mount_radio->setName('/radio.mp3');
        $mount_radio->setIsDefault(true);
        $em->persist($mount_radio);

        $mount_mobile = new Entity\StationMount($station);
        $mount_mobile->setName('/mobile.mp3');
        $mount_mobile->setAutodjBitrate(64);
        $em->persist($mount_mobile);

        $em->flush();
    }

    public function getDependencies()
    {
        return [
            Station::class,
        ];
    }
}
