<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;

/**
 * @extends AbstractStationBasedRepository<\App\Entity\StationHlsStream>
 */
final class StationHlsStreamRepository extends AbstractStationBasedRepository
{
    /**
     * @param \App\Entity\Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Station $station): array
    {
        $streams = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        /** @var \App\Entity\StationHlsStream $stream */
        foreach ($streams as $stream) {
            $displayNames[$stream->getIdRequired()] = 'HLS: ' . $stream->getName();
        }

        return $displayNames;
    }
}
