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
        $station->name = 'AzuraTest Radio';
        $station->description = 'A test radio station.';
        $station->enable_requests = true;
        $station->frontend_type = FrontendAdapters::Icecast;
        $station->backend_type = BackendAdapters::Liquidsoap;
        $station->enable_hls = true;
        $station->radio_base_dir = '/var/azuracast/stations/azuratest_radio';
        $station->has_started = true;
        $station->ensureDirectoriesExist();

        $mediaStorage = $station->media_storage_location;
        $recordingsStorage = $station->recordings_storage_location;
        $podcastsStorage = $station->podcasts_storage_location;

        $stationQuota = getenv('INIT_STATION_QUOTA');
        if (!empty($stationQuota)) {
            $mediaStorage->storageQuota = $stationQuota;
            $recordingsStorage->storageQuota = $stationQuota;
            $podcastsStorage->storageQuota = $stationQuota;
        }

        $manager->persist($station);
        $manager->persist($mediaStorage);
        $manager->persist($recordingsStorage);
        $manager->persist($podcastsStorage);

        $manager->flush();

        $this->addReference('station', $station);
    }
}
