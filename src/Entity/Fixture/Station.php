<?php
namespace App\Entity\Fixture;

use App\Radio\Adapters;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity;

class Station extends AbstractFixture
{
    public function load(ObjectManager $em)
    {
        $station = new Entity\Station;
        $station->setName('AzuraTest Radio');
        $station->setDescription('A test radio station.');
        $station->setFrontendType(Adapters::FRONTEND_ICECAST);
        $station->setBackendType(Adapters::BACKEND_LIQUIDSOAP);
        $station->setRadioBaseDir('/var/azuracast/stations/azuratest_radio');

        $station_quota = getenv('INIT_STATION_QUOTA');
        if (!empty($station_quota)) {
            $station->setStorageQuota($station_quota);
        }

        $em->persist($station);
        $em->flush();

        $this->addReference('station', $station);
    }
}
