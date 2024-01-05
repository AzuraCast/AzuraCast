<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Interfaces\StationAwareInterface;
use App\Entity\Station;
use App\Exception\NotFoundException;

/**
 * @template TEntity as object
 * @extends Repository<TEntity>
 */
abstract class AbstractStationBasedRepository extends Repository
{
    /**
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

    /**
     * @return TEntity
     */
    public function requireForStation(int|string $id, Station $station): object
    {
        $record = $this->findForStation($id, $station);
        if (null === $record) {
            throw NotFoundException::generic();
        }
        return $record;
    }
}
