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
        $station = $this->getReference('station', Station::class);

        $mountRadio = new StationMount($station);
        $mountRadio->name = '/radio.mp3';
        $mountRadio->is_default = true;
        $manager->persist($mountRadio);

        $mountMobile = new StationMount($station);
        $mountMobile->name = '/mobile.mp3';
        $mountMobile->autodj_bitrate = 64;
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
