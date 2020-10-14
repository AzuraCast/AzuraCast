<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class StationMountRepository extends Repository
{
    /**
     * @param Entity\Station $station
     */
    public function getDefaultMount(Entity\Station $station): ?Entity\StationMount
    {
        $mount = $this->repository->findOneBy(['station_id' => $station->getId(), 'is_default' => true]);

        if ($mount instanceof Entity\StationMount) {
            return $mount;
        }

        // Use the first mount if none is specified as default.
        $mount = $station->getMounts()->first();

        if ($mount instanceof Entity\StationMount) {
            $mount->setIsDefault(true);
            $this->em->persist($mount);
            $this->em->flush();

            return $mount;
        }

        return null;
    }
}
