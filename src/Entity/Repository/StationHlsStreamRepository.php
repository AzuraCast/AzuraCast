<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity;

/**
 * @extends AbstractStationBasedRepository<Entity\StationHlsStream>
 */
final class StationHlsStreamRepository extends AbstractStationBasedRepository
{
    /**
     * @param Entity\Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Entity\Station $station): array
    {
        $streams = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        /** @var Entity\StationHlsStream $stream */
        foreach ($streams as $stream) {
            $displayNames[$stream->getIdRequired()] = 'HLS: ' . $stream->getName();
        }

        return $displayNames;
    }
}
