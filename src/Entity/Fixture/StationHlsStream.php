<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use App\Radio\Enums\StreamFormats;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StationHlsStream extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $mountLofi = new Entity\StationHlsStream($station);
        $mountLofi->setName('aac_lofi');
        $mountLofi->setFormat(StreamFormats::Aac->value);
        $mountLofi->setBitrate(64);
        $manager->persist($mountLofi);

        $mountMidfi = new Entity\StationHlsStream($station);
        $mountMidfi->setName('aac_midfi');
        $mountMidfi->setFormat(StreamFormats::Aac->value);
        $mountMidfi->setBitrate(128);
        $manager->persist($mountMidfi);

        $mountHifi = new Entity\StationHlsStream($station);
        $mountHifi->setName('aac_hifi');
        $mountHifi->setFormat(StreamFormats::Aac->value);
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
            Station::class,
        ];
    }
}
