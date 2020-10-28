<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class StorageLocationRepository extends Repository
{
    /**
     * @param string $type
     *
     * @return Entity\StorageLocation[]
     */
    public function findAllByType(string $type): array
    {
        return $this->repository->findBy([
            'type' => $type,
        ]);
    }
}
