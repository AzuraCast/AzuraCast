<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\StationHlsStream;

/**
 * @extends AbstractStationBasedRepository<StationHlsStream>
 */
final class StationHlsStreamRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationHlsStream::class;

    /**
     * @param Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Station $station): array
    {
        $streams = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        /** @var StationHlsStream $stream */
        foreach ($streams as $stream) {
            $displayNames[$stream->getIdRequired()] = 'HLS: ' . $stream->getName();
        }

        return $displayNames;
    }
}
