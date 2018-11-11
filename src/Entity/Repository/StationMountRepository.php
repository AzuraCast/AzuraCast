<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;

class StationMountRepository extends Repository
{
    /**
     * @param Entity\Station $station
     * @return Entity\StationMount|null
     */
    public function getDefaultMount(Entity\Station $station): ?Entity\StationMount
    {
        $mount = $this->findOneBy(['station_id' => $station->getId(), 'is_default' => true]);

        if ($mount instanceof Entity\StationMount) {
            return $mount;
        }

        // Use the first mount if none is specified as default.
        $mount = $station->getMounts()->first();

        if ($mount instanceof Entity\StationMount) {
            $mount->setIsDefault(true);
            $this->_em->persist($mount);
            $this->_em->flush();

            return $mount;
        }

        return null;
    }
}
