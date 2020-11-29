<?php

namespace App\Entity\Fixture;

use App\Entity;
use App\Radio\Adapters;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class Station extends AbstractFixture
{
    public function load(ObjectManager $em): void
    {
        $station = new Entity\Station();
        $station->setName('AzuraTest Radio');
        $station->setDescription('A test radio station.');
        $station->setEnableRequests(true);
        $station->setFrontendType(Adapters::FRONTEND_ICECAST);
        $station->setBackendType(Adapters::BACKEND_LIQUIDSOAP);
        $station->setRadioBaseDir('/var/azuracast/stations/azuratest_radio');

        $station->ensureDirectoriesExist();

        $mediaStorage = $station->getMediaStorageLocation();
        $recordingsStorage = $station->getRecordingsStorageLocation();

        $stationQuota = getenv('INIT_STATION_QUOTA');
        if (!empty($stationQuota)) {
            $mediaStorage->setStorageQuota($stationQuota);
            $recordingsStorage->setStorageQuota($stationQuota);
        }

        $em->persist($station);
        $em->persist($mediaStorage);
        $em->persist($recordingsStorage);

        $em->flush();

        $this->addReference('station', $station);
    }
}
