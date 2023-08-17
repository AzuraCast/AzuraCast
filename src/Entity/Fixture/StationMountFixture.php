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

        $mountRadio = new StationMount($station);
        $mountRadio->setName('/radio.mp3');
        $mountRadio->setIsDefault(true);
        $manager->persist($mountRadio);

        $mountMobile = new StationMount($station);
        $mountMobile->setName('/mobile.mp3');
        $mountMobile->setAutodjBitrate(64);
        $manager->persist($mountMobile);

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
