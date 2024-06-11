<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use Doctrine\ORM\Query;

final class CheckFolderPlaylistsTask extends AbstractTask
{
    public function __construct(
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '*/5 * * * *';
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            $this->syncPlaylistFolders($station);
        }
    }

    public function syncPlaylistFolders(Station $station): void
    {
        $this->logger->info(
            'Processing auto-assigning folders for station...',
            [
                'station' => $station->getName(),
            ]
        );

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $mediaInPlaylistQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT spm.media_id
                FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist_id = :playlist_id
            DQL
        );

        $mediaInFolderQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT sm.id
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
                AND sm.path LIKE :path
            DQL
        )->setParameter('storageLocation', $station->getMediaStorageLocation());

        foreach ($station->getPlaylists() as $playlist) {
            if (PlaylistSources::Songs !== $playlist->getSource()) {
                continue;
            }

            $this->em->wrapInTransaction(
                fn() => $this->processPlaylist(
                    $playlist,
                    $fsMedia,
                    $mediaInPlaylistQuery,
                    $mediaInFolderQuery
                )
            );
        }
    }

    private function processPlaylist(
        StationPlaylist $playlist,
        ExtendedFilesystemInterface $fsMedia,
        Query $mediaInPlaylistQuery,
        Query $mediaInFolderQuery
    ): void {
        $folders = $playlist->getFolders();
        if (0 === $folders->count()) {
            return;
        }

        // Get all media IDs that are already in the playlist.
        $mediaInPlaylistRaw = $mediaInPlaylistQuery->setParameter('playlist_id', $playlist->getId())
            ->getArrayResult();
        $mediaInPlaylist = array_column($mediaInPlaylistRaw, 'media_id', 'media_id');

        foreach ($folders as $folder) {
            $path = $folder->getPath();

            // Verify the folder still exists.
            if (!$fsMedia->isDir($path)) {
                $this->em->remove($folder);
                continue;
            }

            $mediaInFolderRaw = $mediaInFolderQuery->setParameter('path', $path . '/%')
                ->getArrayResult();

            $addedRecords = 0;

            foreach ($mediaInFolderRaw as $row) {
                $mediaId = $row['id'];

                if (!isset($mediaInPlaylist[$mediaId])) {
                    $media = $this->em->find(StationMedia::class, $mediaId);

                    if ($media instanceof StationMedia) {
                        $this->spmRepo->addMediaToPlaylist($media, $playlist);

                        $mediaInPlaylist[$mediaId] = $mediaId;
                        $addedRecords++;
                    }
                }
            }

            $logMessage = (0 === $addedRecords)
                ? 'No changes detected in folder.'
                : sprintf('%d media records added from folder.', $addedRecords);

            $this->logger->debug(
                $logMessage,
                [
                    'playlist' => $playlist->getName(),
                    'folder' => $folder->getPath(),
                ]
            );
        }
    }
}
