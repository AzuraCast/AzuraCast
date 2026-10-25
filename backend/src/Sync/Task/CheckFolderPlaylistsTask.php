<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StorageLocation;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Message\WritePlaylistFileMessage;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Messenger\MessageBus;

final class CheckFolderPlaylistsTask extends AbstractTask
{
    public function __construct(
        private readonly StationPlaylistFolderRepository $folderRepo,
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StationFilesystems $stationFilesystems,
        private readonly MessageBus $messageBus,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '*/5 * * * *';
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            try {
                $this->syncPlaylistFolders($station);
            } catch (FilesystemException $exception) {
                $this->logger->error(
                    "Flysystem Error for Station {$station->name}",
                    [
                        'exception' => $exception,
                    ]
                );
            }
        }
    }

    public function syncPlaylistFolders(Station $station): void
    {
        $this->logger->info(
            'Processing auto-assigning folders for station...',
            [
                'station' => $station->name,
            ]
        );

        $mediaStorage = $station->media_storage_location;
        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        foreach ($station->playlists as $playlist) {
            if (PlaylistSources::Songs !== $playlist->source) {
                continue;
            }

            $this->em->wrapInTransaction(
                fn() => $this->processPlaylist(
                    $playlist,
                    $mediaStorage,
                    $fsMedia
                )
            );
        }
    }

    private function processPlaylist(
        StationPlaylist $playlist,
        StorageLocation $mediaStorageLocation,
        ExtendedFilesystemInterface $fsMedia
    ): void {
        $folders = $playlist->folders;
        if (0 === $folders->count()) {
            return;
        }

        $madeChanges = false;

        foreach ($folders as $folder) {
            $path = $folder->path;

            $mediaInFolder = $this->folderRepo->getMediaIdsInFolder(
                $mediaStorageLocation,
                $path
            );

            if ($mediaInFolder === [] && $this->isFolderDeleted($fsMedia, $path)) {
                $this->em->remove($folder);
                $madeChanges = true;
                continue;
            }

            // Remove media from this folder that isn't in it anymore.
            $removedRecords = 0;

            foreach ($folder->media_items as $spm) {
                if (isset($mediaInFolder[$spm->media_id])) {
                    unset($mediaInFolder[$spm->media_id]);
                } else {
                    $this->em->remove($spm);
                    $removedRecords++;
                }
            }

            $addedRecords = 0;
            $weight = $this->spmRepo->getHighestSongWeight($playlist);

            foreach ($mediaInFolder as $mediaId) {
                $media = $this->em->find(StationMedia::class, $mediaId);

                if ($media instanceof StationMedia) {
                    $this->spmRepo->addMediaToPlaylist($media, $playlist, $weight, $folder);
                    $weight++;
                    $addedRecords++;
                }
            }

            if ($addedRecords > 0 || $removedRecords > 0) {
                $madeChanges = true;

                $this->logger->debug(
                    sprintf(
                        '%d media added, %d media removed from folder.',
                        $addedRecords,
                        $removedRecords
                    ),
                    [
                        'playlist' => $playlist->name,
                        'folder' => $folder->path,
                    ]
                );
            } else {
                $this->logger->debug(
                    'No changes detected in folder.',
                    [
                        'playlist' => $playlist->name,
                        'folder' => $folder->path,
                    ]
                );
            }
        }

        if ($madeChanges) {
            // Write changes to file.
            $message = new WritePlaylistFileMessage();
            $message->playlist_id = $playlist->id;

            $this->messageBus->dispatch($message);
        }
    }

    private function isFolderDeleted(ExtendedFilesystemInterface $fsMedia, string $path): bool
    {
        try {
            return !$fsMedia->directoryExists($path) && $this->isStorageVisible($fsMedia);
        } catch (FilesystemException $exception) {
            $this->logger->warning(
                "Could not check the folder \"{$path}\" in the media storage. Keeping its playlist link.",
                [
                    'exception' => $exception,
                ]
            );
            return false;
        }
    }

    private function isStorageVisible(ExtendedFilesystemInterface $fsMedia): bool
    {
        $rootEntries = $fsMedia->listContents('/', false)->filter(
            fn(StorageAttributes $attrs) => !StationFilesystems::isDotFile($attrs->path())
        );

        return $rootEntries->toArray() !== [];
    }
}
