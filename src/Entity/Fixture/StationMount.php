<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StationMount extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $mount_radio = new Entity\StationMount($station);
        $mount_radio->setName('/radio.mp3');
        $mount_radio->setIsDefault(true);
        $manager->persist($mount_radio);

        $mount_mobile = new Entity\StationMount($station);
        $mount_mobile->setName('/mobile.mp3');
        $mount_mobile->setAutodjBitrate(64);
        $manager->persist($mount_mobile);

        $manager->flush();
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
