<?php
namespace Entity\Repository;

use Entity;

class StationMountRepository extends \App\Doctrine\Repository
{
    /**
     * @param Entity\Station $station
     * @return null|object
     */
    public function getDefaultMount(Entity\Station $station)
    {
        return $this->findOneBy(['station_id' => $station->getId(), 'is_default' => true]);
    }
}