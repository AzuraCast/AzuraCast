<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Interfaces\StationAwareInterface;
use App\Entity\Station;

/**
 * @template TEntity as object
 */
abstract class AbstractStationBasedRepository extends Repository
{
    /**
     * @param int|string $id
     * @param Station $station
     * @return TEntity|null
     */
    public function findForStation(int|string $id, Station $station): ?object
    {
        $record = $this->find($id);

        if ($record instanceof StationAwareInterface && $station === $record->getStation()) {
            return $record;
        }

        return null;
    }
}
