<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Flysystem\StationFilesystems;
use Azura\Files\ExtendedFilesystemInterface;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;

class CheckFolderPlaylistsTask extends AbstractTask
{
    public function __construct(
        protected Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        protected Entity\Repository\StationPlaylistFolderRepository $folderRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            $this->syncPlaylistFolders($station);
        }
    }

    public function syncPlaylistFolders(Entity\Station $station): void
    {
        $this->logger->info(
            'Processing auto-assigning folders for station...',
            [
                'station' => $station->getName(),
            ]
        );

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

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
            if (Entity\StationPlaylist::SOURCE_SONGS !== $playlist->getSource()) {
                continue;
            }

            $this->em->transactional(
                function () use ($station, $playlist, $fsMedia, $mediaInPlaylistQuery, $mediaInFolderQuery): void {
                    $this->processPlaylist(
                        $station,
                        $playlist,
                        $fsMedia,
                        $mediaInPlaylistQuery,
                        $mediaInFolderQuery
                    );
                }
            );
        }
    }

    protected function processPlaylist(
        Entity\Station $station,
        Entity\StationPlaylist $playlist,
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
                    $media = $this->em->find(Entity\StationMedia::class, $mediaId);

                    if ($media instanceof Entity\StationMedia) {
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
