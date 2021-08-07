<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class StationRemoteRepository extends Repository
{
    /**
     * @param Entity\Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Entity\Station $station): array
    {
        $remotes = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        foreach ($remotes as $remote) {
            /** @var Entity\StationRemote $remote */
            $displayNames[$remote->getId()] = $remote->getDisplayName();
        }

        return $displayNames;
    }
}
