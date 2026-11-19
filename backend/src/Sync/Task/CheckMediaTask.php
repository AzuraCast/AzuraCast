<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Repository\UnprocessableMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StorageLocation;
use App\Entity\UnprocessableMedia;
use App\Flysystem\Attributes\FileAttributes;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Media\MimeType;
use App\Message\AddNewMediaMessage;
use App\Message\ProcessCoverArtMessage;
use App\Message\ReprocessMediaMessage;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use App\Radio\Quota;
use App\Utilities\Types;
use Brick\Math\BigInteger;
use Doctrine\ORM\AbstractQuery;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Messenger\MessageBus;

/**
 * @phpstan-type MediaSyncStats array{
 *  total_size: string,
 *  total_files: int,
 *  unchanged: int,
 *  updated: int,
 *  created: int,
 *  deleted: int,
 *  cover_art: int,
 *  not_processable: int,
 * }
 */
final class CheckMediaTask extends AbstractTask
{
    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly UnprocessableMediaRepository $unprocessableMediaRepo,
        private readonly StorageLocationRepository $storageLocationRepo,
        private readonly MessageBus $messageBus,
        private readonly QueueManagerInterface $queueManager
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '1-59/5 * * * *';
    }

    public static function isLongTask(): bool
    {
        return true;
    }

    public function run(bool $force = false): void
    {
        // Clear existing media queue.
        $this->queueManager->clearQueue(QueueNames::SlowMedia);

        // Process for each storage location.
        $storageLocations = $this->iterateStorageLocations(StorageLocationTypes::StationMedia);

        foreach ($storageLocations as $storageLocation) {
            $this->logger->info(
                sprintf(
                    'Processing media for storage location %s...',
                    $storageLocation
                )
            );

            $this->importMusic(
                $storageLocation
            );
        }
    }

    /**
     * @return ?MediaSyncStats Null when the listing failed or the media cleanup was skipped
     */
    public function importMusic(
        StorageLocation $storageLocation,
        bool $allowEmptyStorage = false
    ): ?array {
        $stats = [
            'total_size' => '0',
            'total_files' => 0,
            'unchanged' => 0,
            'updated' => 0,
            'created' => 0,
            'deleted' => 0,
            'cover_art' => 0,
            'not_processable' => 0,
        ];

        $totalSize = BigInteger::zero();
        $musicFiles = [];
        $coverFiles = [];

        try {
            $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

            $fsIterator = $fs->listContents('/', true)->filter(
                fn(StorageAttributes $attrs) => $attrs->isFile() && !StationFilesystems::isDotFile($attrs->path())
            );

            /** @var FileAttributes $file */
            foreach ($fsIterator as $file) {
                try {
                    $size = $file->fileSize();
                    $totalSize = $totalSize->plus($size);
                } catch (UnableToRetrieveMetadata) {
                    continue;
                }

                if ($size === 0) {
                    continue;
                }

                if (MimeType::isPathProcessable($file->path())) {
                    $pathHash = md5($file->path());
                    $musicFiles[$pathHash] = [
                        StorageAttributes::ATTRIBUTE_PATH => $file->path(),
                        StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $file->lastModified(),
                        StorageAttributes::ATTRIBUTE_FILE_SIZE => $size,
                    ];
                } elseif (MimeType::isPathImage($file->path())) {
                    $stats['cover_art']++;

                    $dirHash = StationMedia::getFolderHashForPath($file->path());
                    $coverFiles[$dirHash] = [
                        StorageAttributes::ATTRIBUTE_PATH => $file->path(),
                        StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $file->lastModified(),
                    ];
                } else {
                    $stats['not_processable']++;
                }
            }
        } catch (FilesystemException $exception) {
            $this->logger->error(
                sprintf('Flysystem Error for Storage Space %s', $storageLocation),
                [
                    'exception' => $exception,
                ]
            );
            return null;
        }

        if ($musicFiles === [] && $this->hasMedia($storageLocation)) {
            if (!$allowEmptyStorage) {
                $this->logger->warning(
                    sprintf(
                        <<<'TEXT'
                        Storage location "%s" shows no music files while media is still in DB. Skipping media cleanup.
                        Check that the storage is mounted and reachable.
                        If it was emptied on purpose, run "Sync Media (Allow Empty Storage)" in the System Debugger.
                        Or run "azuracast:media:sync <station> --allow-empty-storage" on the command line.
                        TEXT,
                        $storageLocation
                    )
                );
                return null;
            }

            $this->logger->notice(
                sprintf(
                    <<<'TEXT'
                    Storage location "%s" shows no music files.
                    Removing its media because an empty storage is allowed.
                    TEXT,
                    $storageLocation
                )
            );
        }

        $storageLocation->storageUsed = $totalSize;
        $this->em->persist($storageLocation);
        $this->em->flush();

        $stats['total_size'] = $totalSize . ' (' . Quota::getReadableSize($totalSize) . ')';
        $stats['total_files'] = count($musicFiles);

        // Process cover art.
        $this->processCoverArt($storageLocation, $fs, $coverFiles);

        // Check queue for existing pending processing entries.
        $this->processExistingMediaRows($storageLocation, $musicFiles, $stats);

        $storageLocation = $this->em->refetch($storageLocation);

        // Loop through currently unprocessable media.
        $this->processUnprocessableMediaRows($storageLocation, $musicFiles, $stats);

        $storageLocation = $this->em->refetch($storageLocation);

        $this->processNewFiles($storageLocation, $musicFiles, $stats);

        $this->logger->debug(sprintf('Media processed for "%s".', $storageLocation), $stats);

        return $stats;
    }

    private function hasMedia(StorageLocation $storageLocation): bool
    {
        return $this->em->getRepository(StationMedia::class)
            ->count(['storage_location' => $storageLocation]) > 0;
    }

    private function processCoverArt(
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs,
        array $coverFiles
    ): void {
        $fsIterator = $fs->listContents(StationFilesystems::DIR_FOLDER_COVERS, true)->filter(
            fn(StorageAttributes $attrs) => $attrs->isFile()
        );

        /** @var FileAttributes $file */
        foreach ($fsIterator as $file) {
            $basename = Path::getFilenameWithoutExtension($file->path(), '.jpg');

            if (!isset($coverFiles[$basename])) {
                $fs->delete($file->path());
            } elseif ($file->lastModified() > $coverFiles[$basename][StorageAttributes::ATTRIBUTE_LAST_MODIFIED]) {
                unset($coverFiles[$basename]);
            }
        }

        foreach ($coverFiles as $folderHash => $coverFile) {
            $message = new ProcessCoverArtMessage();
            $message->storage_location_id = $storageLocation->id;
            $message->path = $coverFile[StorageAttributes::ATTRIBUTE_PATH];
            $message->folder_hash = $folderHash;

            $this->messageBus->dispatch($message);
        }
    }

    /**
     * @param MediaSyncStats $stats
     */
    private function processExistingMediaRows(
        StorageLocation $storageLocation,
        array &$musicFiles,
        array &$stats
    ): void {
        $existingMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT sm.id, sm.path, sm.mtime, sm.unique_id, sm.size
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        $updateMediaSizeQuery = $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.size = :size
                WHERE sm.id = :id
            DQL
        );

        /** @var array<array-key, int|string> $mediaRow */
        foreach ($existingMediaQuery->toIterable([], AbstractQuery::HYDRATE_ARRAY) as $mediaRow) {
            // Check if media file still exists.
            $path = (string)$mediaRow['path'];
            $pathHash = md5($path);

            if (isset($musicFiles[$pathHash])) {
                $fileInfo = $musicFiles[$pathHash];
                $mtime = $fileInfo[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? 0;
                $mediaProcessedAt = Types::int($mediaRow['mtime']);

                if ($mtime > $mediaProcessedAt) {
                    $message = new ReprocessMediaMessage();
                    $message->storage_location_id = $storageLocation->id;
                    $message->media_id = (int)$mediaRow['id'];

                    $this->messageBus->dispatch($message);
                    $stats['updated']++;
                } else {
                    // Keep the stored file size in sync without reprocessing the file.
                    $fileSize = $fileInfo[StorageAttributes::ATTRIBUTE_FILE_SIZE];
                    if ($fileSize !== Types::int($mediaRow['size'])) {
                        $updateMediaSizeQuery->setParameter('size', $fileSize)
                            ->setParameter('id', $mediaRow['id'])
                            ->execute();
                    }

                    $stats['unchanged']++;
                }

                unset($musicFiles[$pathHash]);
            } else {
                $media = $this->em->find(StationMedia::class, $mediaRow['id']);
                if ($media instanceof StationMedia) {
                    $this->mediaRepo->remove($media);
                }

                $stats['deleted']++;
            }
        }

        $this->em->clear();
    }

    /**
     * @param MediaSyncStats $stats
     */
    private function processUnprocessableMediaRows(
        StorageLocation $storageLocation,
        array &$musicFiles,
        array &$stats
    ): void {
        $unprocessableMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT upm.id, upm.path, upm.mtime
                FROM App\Entity\UnprocessableMedia upm
                WHERE upm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        $unprocessableRecords = $unprocessableMediaQuery->toIterable([], AbstractQuery::HYDRATE_ARRAY);

        foreach ($unprocessableRecords as $unprocessableRow) {
            $pathHash = md5($unprocessableRow['path']);

            if (isset($musicFiles[$pathHash])) {
                $fileInfo = $musicFiles[$pathHash];
                $mtime = $fileInfo[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? 0;

                if (UnprocessableMedia::needsReprocessing($mtime, Types::int($unprocessableRow['mtime']))) {
                    $message = new AddNewMediaMessage();
                    $message->storage_location_id = $storageLocation->id;
                    $message->path = $unprocessableRow['path'];

                    $this->messageBus->dispatch($message);

                    $stats['updated']++;
                } else {
                    $stats['not_processable']++;
                }

                unset($musicFiles[$pathHash]);
            } else {
                $this->unprocessableMediaRepo->clearForPath($storageLocation, $unprocessableRow['path']);
            }
        }

        $this->em->clear();
    }

    /**
     * @param MediaSyncStats $stats
     */
    private function processNewFiles(
        StorageLocation $storageLocation,
        array $musicFiles,
        array &$stats
    ): void {
        foreach ($musicFiles as $newMusicFile) {
            $path = $newMusicFile[StorageAttributes::ATTRIBUTE_PATH];

            $message = new AddNewMediaMessage();
            $message->storage_location_id = $storageLocation->id;
            $message->path = $path;

            $this->messageBus->dispatch($message);

            $stats['created']++;
        }
    }
}
