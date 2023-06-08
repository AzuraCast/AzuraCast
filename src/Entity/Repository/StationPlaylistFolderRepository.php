<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;

/**
 * @extends AbstractStationBasedRepository<StationPlaylistFolder>
 */
final class StationPlaylistFolderRepository extends AbstractStationBasedRepository
{
    /**
     * @param Station $station
     * @param StationPlaylist[] $playlists
     * @param string $path
     */
    public function setPlaylistsForFolder(
        Station $station,
        array $playlists,
        string $path
    ): void {
        if (str_contains($path, '://')) {
            [, $path] = explode('://', $path, 2);
        }

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistFolder spf
                WHERE spf.station = :station AND spf.path = :path
            DQL
        )->setParameter('station', $station)
            ->setParameter('path', $path)
            ->execute();

        foreach ($playlists as $playlistId => $playlistRecord) {
            if (PlaylistSources::Songs === $playlistRecord->getSource()) {
                /** @var StationPlaylist $playlist */
                $playlist = $this->em->getReference(StationPlaylist::class, $playlistId);

                $newRecord = new StationPlaylistFolder($station, $playlist, $path);
                $this->em->persist($newRecord);
            }
        }

        $this->em->flush();
    }
}
