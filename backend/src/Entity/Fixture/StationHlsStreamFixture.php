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
        /** @var Station $station */
        $station = $this->getReference('station');

        $mountLofi = new StationHlsStream($station);
        $mountLofi->setName('aac_lofi');
        $mountLofi->setFormat(StreamFormats::Aac);
        $mountLofi->setBitrate(64);
        $manager->persist($mountLofi);

        $mountMidfi = new StationHlsStream($station);
        $mountMidfi->setName('aac_midfi');
        $mountMidfi->setFormat(StreamFormats::Aac);
        $mountMidfi->setBitrate(128);
        $manager->persist($mountMidfi);

        $mountHifi = new StationHlsStream($station);
        $mountHifi->setName('aac_hifi');
        $mountHifi->setFormat(StreamFormats::Aac);
        $mountHifi->setBitrate(256);
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
