<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

/**
 * @extends Repository<Entity\StationPlaylist>
 */
class StationPlaylistRepository extends Repository
{
    /**
     * @return Entity\StationPlaylist[]
     */
    public function getAllForStation(Entity\Station $station): array
    {
        return $this->repository->findBy([
            'station' => $station,
        ]);
    }

    public function stationHasActivePlaylists(Entity\Station $station): bool
    {
        foreach ($station->getPlaylists() as $playlist) {
            if (!$playlist->getIsEnabled()) {
                continue;
            }

            if (Entity\Enums\PlaylistSources::RemoteUrl === $playlist->getSourceEnum()) {
                return true;
            }

            $mediaCount = $this->em->createQuery(
                <<<DQL
                    SELECT COUNT(spm.id) FROM App\Entity\StationPlaylistMedia spm
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
