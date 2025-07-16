<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Station;
use App\Entity\StationHlsStream;
use App\Radio\Enums\StreamFormats;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StationHlsStreamFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $station = $this->getReference('station', Station::class);

        $mountLofi = new StationHlsStream($station);
        $mountLofi->name = 'aac_lofi';
        $mountLofi->format = StreamFormats::Aac;
        $mountLofi->bitrate = 64;
        $manager->persist($mountLofi);

        $mountMidfi = new StationHlsStream($station);
        $mountMidfi->name = 'aac_midfi';
        $mountMidfi->format = StreamFormats::Aac;
        $mountMidfi->bitrate = 128;
        $manager->persist($mountMidfi);

        $mountHifi = new StationHlsStream($station);
        $mountHifi->name = 'aac_hifi';
        $mountHifi->format = StreamFormats::Aac;
        $mountHifi->bitrate = 256;
        $manager->persist($mountHifi);

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
