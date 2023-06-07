<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Station;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

final class StationFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $station = new Station();
        $station->setName('AzuraTest Radio');
        $station->setDescription('A test radio station.');
        $station->setEnableRequests(true);
        $station->setFrontendType(FrontendAdapters::Icecast);
        $station->setBackendType(BackendAdapters::Liquidsoap);
        $station->setEnableHls(true);
        $station->setRadioBaseDir('/var/azuracast/stations/azuratest_radio');
        $station->setHasStarted(true);
        $station->ensureDirectoriesExist();

        $mediaStorage = $station->getMediaStorageLocation();
        $recordingsStorage = $station->getRecordingsStorageLocation();
        $podcastsStorage = $station->getPodcastsStorageLocation();

        $stationQuota = getenv('INIT_STATION_QUOTA');
        if (!empty($stationQuota)) {
            $mediaStorage->setStorageQuota($stationQuota);
            $recordingsStorage->setStorageQuota($stationQuota);
            $podcastsStorage->setStorageQuota($stationQuota);
        }

        $manager->persist($station);
        $manager->persist($mediaStorage);
        $manager->persist($recordingsStorage);
        $manager->persist($podcastsStorage);

        $manager->flush();

        $this->addReference('station', $station);
    }
}
