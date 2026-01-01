<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StorageLocation;

/**
 * @extends AbstractStationBasedRepository<StationPlaylistFolder>
 */
final class StationPlaylistFolderRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationPlaylistFolder::class;

    public function __construct(
        private readonly StationPlaylistMediaRepository $spmRepo
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function getMediaIdsInFolder(
        Station|StorageLocation $location,
        string $path,
    ): array {
        if ($location instanceof Station) {
            $location = $location->media_storage_location;
        }

        $mediaInFolderRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT sm.id
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
                AND sm.path LIKE :path
            DQL
        )->setParameter('storageLocation', $location)
            ->setParameter('path', $path . '/%')
            ->getArrayResult();

        return array_column($mediaInFolderRaw, 'id', 'id');
    }

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

        $mediaInFolder = $this->getMediaIdsInFolder($station, $path);

        foreach ($playlists as $playlistId => $playlistWeight) {
            /** @var StationPlaylist $playlist */
            $playlist = $this->em->getReference(StationPlaylist::class, $playlistId);

            $folder = new StationPlaylistFolder($station, $playlist, $path);
            $this->em->persist($folder);

            if (count($mediaInFolder) > 0) {
                $weight = $this->spmRepo->getHighestSongWeight($playlist);

                foreach ($mediaInFolder as $mediaId) {
                    $media = $this->em->find(StationMedia::class, $mediaId);

                    if ($media !== null) {
                        $this->spmRepo->addMediaToPlaylist($media, $playlist, $weight, $folder);
                        $weight++;
                    }
                }
            }
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
                AND IDENTITY(spf.playlist) IN (:playlistIds)
            DQL
            )->setParameter('station', $station)
                ->setParameter('path', $path)
                ->setParameter('playlistIds', $toDelete)
                ->execute();
        }

        $this->addPlaylistsToFolder(
            $station,
            $path,
            $playlists
        );
    }

    /**
     * @return StationPlaylistFolder[]
     */
    public function getPlaylistFoldersForPath(
        Station $station,
        string $path,
        bool $includeParents = false
    ): array {
        $path = self::filterPath($path);

        /** @var StationPlaylistFolder[] $folders */
        $folders = $this->repository->findBy([
            'path' => $path,
            'station' => $station,
        ]);

        if (!$includeParents) {
            return $folders;
        }

        for ($i = 0; $i <= substr_count($path, '/'); $i++) {
            $pathToSearch = implode('/', explode('/', $path, 0 - $i));

            $folders = array_merge(
                $folders,
                $this->repository->findBy([
                    'path' => self::filterPath($pathToSearch),
                    'station' => $station,
                ])
            );
        }

        /** @var array<int, StationPlaylistFolder[]> $foldersByPlaylist */
        $foldersByPlaylist = [];
        foreach ($folders as $folder) {
            $foldersByPlaylist[$folder->playlist->id] ??= [];
            $foldersByPlaylist[$folder->playlist->id][] = $folder;
        }

        /** @var StationPlaylistFolder[] $uniqueFolders */
        $uniqueFolders = [];

        foreach ($foldersByPlaylist as $playlistFolders) {
            if (count($playlistFolders) > 1) {
                // Get the folder highest up in the hierarchy (with the shortest path)
                uasort(
                    $playlistFolders,
                    fn(StationPlaylistFolder $a, StationPlaylistFolder $b) => strlen($a->path) <=> strlen($b->path),
                );
            }

            if ($firstFolder = reset($playlistFolders)) {
                $uniqueFolders[] = $firstFolder;
            }
        }

        return $uniqueFolders;
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
                SELECT IDENTITY(spf.playlist) AS playlist_id
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
