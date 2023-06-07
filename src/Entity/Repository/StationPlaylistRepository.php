<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\Enums\PlaylistSources;

/**
 * @extends AbstractStationBasedRepository<\App\Entity\StationPlaylist>
 */
final class StationPlaylistRepository extends AbstractStationBasedRepository
{
    /**
     * @return \App\Entity\StationPlaylist[]
     */
    public function getAllForStation(Station $station): array
    {
        return $this->repository->findBy([
            'station' => $station,
        ]);
    }

    public function stationHasActivePlaylists(Station $station): bool
    {
        foreach ($station->getPlaylists() as $playlist) {
            if (!$playlist->getIsEnabled()) {
                continue;
            }

            if (PlaylistSources::RemoteUrl === $playlist->getSource()) {
                return true;
            }

            $mediaCount = $this->em->createQuery(
                <<<DQL
                    SELECT COUNT(spm.id) FROM App\\App\Entity\StationPlaylistMedia spm
                    JOIN spm.playlist sp
                    WHERE sp.station = :station
                DQL
            )->setParameter('station', $station)
                ->getSingleScalarResult();

            if ($mediaCount > 0) {
                return true;
            }
        }

        return false;
    }
}
