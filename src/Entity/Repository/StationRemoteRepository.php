<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\StationRemote;

/**
 * @extends AbstractStationBasedRepository<StationRemote>
 */
final class StationRemoteRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationRemote::class;

    /**
     * @param Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Station $station): array
    {
        $remotes = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        foreach ($remotes as $remote) {
            /** @var StationRemote $remote */
            $displayNames[$remote->getId()] = $remote->getDisplayName();
        }

        return $displayNames;
    }
}
