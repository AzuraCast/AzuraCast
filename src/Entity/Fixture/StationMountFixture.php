<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Station;
use App\Entity\StationMount;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StationMountFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Station $station */
        $station = $this->getReference('station');

        $mount_radio = new StationMount($station);
        $mount_radio->setName('/radio.mp3');
        $mount_radio->setIsDefault(true);
        $manager->persist($mount_radio);

        $mount_mobile = new StationMount($station);
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
            StationFixture::class,
        ];
    }
}
