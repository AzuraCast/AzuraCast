<?php
namespace App\Entity\Fixture;

use App\Entity;
use App\Radio\Adapters;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class Station extends AbstractFixture
{
    public function load(ObjectManager $em)
    {
        $station = new Entity\Station;
        $station->setName('AzuraTest Radio');
        $station->setDescription('A test radio station.');
        $station->setEnableRequests(true);
        $station->setFrontendType(Adapters::FRONTEND_ICECAST);
        $station->setBackendType(Adapters::BACKEND_LIQUIDSOAP);
        $station->setRadioBaseDir('/var/azuracast/stations/azuratest_radio');

        // Ensure all directories exist.
        $radio_dirs = [
            $station->getRadioBaseDir(),
            $station->getRadioMediaDir(),
            $station->getRadioAlbumArtDir(),
            $station->getRadioPlaylistsDir(),
            $station->getRadioConfigDir(),
            $station->getRadioTempDir(),
        ];
        foreach ($radio_dirs as $radio_dir) {
            if (!file_exists($radio_dir) && !mkdir($radio_dir, 0777) && !is_dir($radio_dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $radio_dir));
            }
        }

        $station_quota = getenv('INIT_STATION_QUOTA');
        if (!empty($station_quota)) {
            $station->setStorageQuota($station_quota);
        }

        $em->persist($station);
        $em->flush();

        $this->addReference('station', $station);
    }
}
