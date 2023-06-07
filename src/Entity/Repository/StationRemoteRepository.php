<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;

/**
 * @extends AbstractStationBasedRepository<\App\Entity\StationRemote>
 */
final class StationRemoteRepository extends AbstractStationBasedRepository
{
    /**
     * @param \App\Entity\Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Station $station): array
    {
        $remotes = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        foreach ($remotes as $remote) {
            /** @var \App\Entity\StationRemote $remote */
            $displayNames[$remote->getId()] = $remote->getDisplayName();
        }

        return $displayNames;
    }
}
