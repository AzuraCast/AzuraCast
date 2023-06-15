<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Utilities\Arrays;

/**
 * @extends AbstractStationBasedRepository<StationPlaylistFolder>
 */
final class StationPlaylistFolderRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationPlaylistFolder::class;

    public function addPlaylistsToFolder(
        Station $station,
        string $path,
        array $playlists
    ): void {
        $playlists = $this->getEligiblePlaylists($playlists);

        foreach ($this->getPlaylistIdsForFolder($station, $path) as $playlistId) {
            unset($playlists[$playlistId]);
        }

        foreach ($playlists as $playlistId => $playlistRecord) {
            /** @var StationPlaylist $playlist */
            $playlist = $this->em->getReference(StationPlaylist::class, $playlistId);

            $newRecord = new StationPlaylistFolder($station, $playlist, $path);
            $this->em->persist($newRecord);
        }

        $this->em->flush();
    }

    /**
     * @param Station $station
     * @param string $path
     * @param StationPlaylist[] $playlists
     */
    public function setPlaylistsForFolder(
        Station $station,
        string $path,
        array $playlists
    ): void {
        $playlists = $this->getEligiblePlaylists($playlists);

        $toDelete = [];

        foreach ($this->getPlaylistIdsForFolder($station, $path) as $playlistId) {
            if (isset($playlists[$playlistId])) {
                unset($playlists[$playlistId]);
            } else {
                $toDelete[] = $playlistId;
            }
        }

        if (0 !== count($toDelete)) {
            $this->em->createQuery(
                <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistFolder spf
                WHERE spf.station = :station 
                AND spf.path = :path
                AND spf.playlist_id IN (:playlistIds)
            DQL
            )->setParameter('station', $station)
                ->setParameter('path', $path)
                ->setParameter('playlistIds', $toDelete)
                ->execute();
        }

        foreach ($playlists as $playlistId => $playlistRecord) {
            /** @var StationPlaylist $playlist */
            $playlist = $this->em->getReference(StationPlaylist::class, $playlistId);

            $newRecord = new StationPlaylistFolder($station, $playlist, $path);
            $this->em->persist($newRecord);
        }

        $this->em->flush();
    }

    /**
     * @param array<array-key, StationPlaylist> $playlists
     * @return array<int, StationPlaylist>
     */
    protected function getEligiblePlaylists(array $playlists): array
    {
        return Arrays::keyByCallable(
            array_filter(
                $playlists,
                fn(StationPlaylist $playlist) => PlaylistSources::Songs === $playlist->getSource()
            ),
            fn(StationPlaylist $playlist) => $playlist->getIdRequired()
        );
    }

    /**
     * @return int[]
     */
    protected function getPlaylistIdsForFolder(
        Station $station,
        string $path
    ): array {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT spf.playlist_id
                FROM App\Entity\StationPlaylistFolder spf
                WHERE spf.station = :station AND spf.path = :path
            DQL
        )->setParameter('station', $station)
            ->setParameter('path', $path)
            ->getSingleColumnResult();
    }
}
