<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;

/**
 * @extends AbstractStationBasedRepository<StationPlaylistFolder>
 */
final class StationPlaylistFolderRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationPlaylistFolder::class;

    /**
     * @param Station $station
     * @param string $path
     * @param array<int, int> $playlists An array of Playlist IDs (id => weight)
     */
    public function addPlaylistsToFolder(
        Station $station,
        string $path,
        array $playlists
    ): void {
        $path = self::filterPath($path);

        foreach ($this->getPlaylistIdsForFolder($station, $path) as $playlistId) {
            unset($playlists[$playlistId]);
        }

        foreach ($playlists as $playlistId => $playlistWeight) {
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
     * @param array<int, int> $playlists An array of Playlist IDs (id => weight)
     */
    public function setPlaylistsForFolder(
        Station $station,
        string $path,
        array $playlists
    ): void {
        $path = self::filterPath($path);

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

        foreach ($playlists as $playlistId => $playlistWeight) {
            /** @var StationPlaylist $playlist */
            $playlist = $this->em->getReference(StationPlaylist::class, $playlistId);

            $newRecord = new StationPlaylistFolder($station, $playlist, $path);
            $this->em->persist($newRecord);
        }

        $this->em->flush();
    }

    /**
     * @return int[]
     */
    public function getPlaylistIdsForFolderAndParents(
        Station $station,
        string $path
    ): array {
        $path = self::filterPath($path);
        $playlistIds = [];

        for ($i = 0; $i <= substr_count($path, '/'); $i++) {
            $pathToSearch = implode('/', explode('/', $path, 0 - $i));

            $playlistIds = array_merge($playlistIds, $this->getPlaylistIdsForFolder($station, $pathToSearch));
        }

        return array_unique($playlistIds);
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
            ->setParameter('path', self::filterPath($path))
            ->getSingleColumnResult();
    }

    public static function filterPath(string $path): string
    {
        return trim($path, '/');
    }
}
