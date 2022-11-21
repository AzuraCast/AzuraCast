<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Media\MimeType;
use App\Message\AddNewMediaMessage;
use App\Message\ProcessCoverArtMessage;
use App\Message\ReprocessMediaMessage;
use App\MessageQueue\QueueManagerInterface;
use App\Radio\Quota;
use App\Flysystem\Attributes\FileAttributes;
use App\Flysystem\ExtendedFilesystemInterface;
use Brick\Math\BigInteger;
use Doctrine\ORM\AbstractQuery;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Messenger\MessageBus;

final class CheckMediaTask extends AbstractTask
{
    public function __construct(
        private readonly Entity\Repository\StationMediaRepository $mediaRepo,
        private readonly Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo,
        private readonly MessageBus $messageBus,
        private readonly QueueManagerInterface $queueManager,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
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
        $storageLocations = $this->iterateStorageLocations(Entity\Enums\StorageLocationTypes::StationMedia);

        foreach ($storageLocations as $storageLocation) {
            $this->logger->info(
                sprintf(
                    'Processing media for storage location %s...',
                    $storageLocation
                )
            );

            $this->importMusic($storageLocation);
        }
    }

    public function importMusic(Entity\StorageLocation $storageLocation): void
    {
        $fs = $storageLocation->getFilesystem();

        $stats = [
            'total_size' => '0',
            'total_files' => 0,
            'already_queued' => 0,
            'unchanged' => 0,
            'updated' => 0,
            'created' => 0,
            'deleted' => 0,
            'cover_art' => 0,
            'not_processable' => 0,
        ];

        $total_size = BigInteger::zero();

        try {
            $fsIterator = $fs->listContents('/', true)->filter(
                function (StorageAttributes $attrs) {
                    return ($attrs->isFile()
                        && !str_starts_with($attrs->path(), Entity\StationMedia::DIR_ALBUM_ART)
                        && !str_starts_with($attrs->path(), Entity\StationMedia::DIR_WAVEFORMS)
                        && !str_starts_with($attrs->path(), Entity\StationMedia::DIR_FOLDER_COVERS));
                }
            );
        } catch (FilesystemException $e) {
            $this->logger->error(
                sprintf('Flysystem Error for Storage Space %s', $storageLocation),
                [
                    'exception' => $e,
                ]
            );
            return;
        }

        $musicFiles = [];
        $coverFiles = [];

        /** @var FileAttributes $file */
        foreach ($fsIterator as $file) {
            try {
                $size = $file->fileSize();
                if (null !== $size) {
                    $total_size = $total_size->plus($size);
                }
            } catch (UnableToRetrieveMetadata) {
                continue;
            }

            if (MimeType::isPathProcessable($file->path())) {
                $pathHash = md5($file->path());
                $musicFiles[$pathHash] = [
                    StorageAttributes::ATTRIBUTE_PATH => $file->path(),
                    StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $file->lastModified(),
                ];
            } elseif (MimeType::isPathImage($file->path())) {
                $stats['cover_art']++;

                $dirHash = Entity\StationMedia::getFolderHashForPath($file->path());
                $coverFiles[$dirHash] = [
                    StorageAttributes::ATTRIBUTE_PATH => $file->path(),
                    StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $file->lastModified(),
                ];
            } else {
                $stats['not_processable']++;
            }
        }

        $storageLocation->setStorageUsed($total_size);
        $this->em->persist($storageLocation);
        $this->em->flush();

        $stats['total_size'] = $total_size . ' (' . Quota::getReadableSize($total_size) . ')';
        $stats['total_files'] = count($musicFiles);

        // Check queue for existing pending processing entries.
        $queuedMediaUpdates = [];
        $queuedNewFiles = [];
        $queuedCoverArt = [];

        foreach ($this->queueManager->getMessagesInTransport(QueueManagerInterface::QUEUE_MEDIA) as $message) {
            if ($message instanceof ReprocessMediaMessage) {
                $queuedMediaUpdates[$message->media_id] = true;
            } elseif (
                $message instanceof AddNewMediaMessage
                && $message->storage_location_id === $storageLocation->getId()
            ) {
                $queuedNewFiles[md5($message->path)] = true;
            } elseif (
                $message instanceof ProcessCoverArtMessage
                && $message->storage_location_id === $storageLocation->getId()
            ) {
                $queuedCoverArt[$message->folder_hash] = true;
            }
        }

        // Process cover art.
        $this->processCoverArt($storageLocation, $fs, $coverFiles, $queuedCoverArt);

        // Check queue for existing pending processing entries.
        $this->processExistingMediaRows($storageLocation, $queuedMediaUpdates, $musicFiles, $stats);

        $storageLocation = $this->em->refetch($storageLocation);

        // Loop through currently unprocessable media.
        $this->processUnprocessableMediaRows($storageLocation, $musicFiles, $stats);

        $storageLocation = $this->em->refetch($storageLocation);

        $this->processNewFiles($storageLocation, $queuedNewFiles, $musicFiles, $stats);

        $this->logger->debug(sprintf('Media processed for "%s".', $storageLocation), $stats);
    }

    private function processCoverArt(
        Entity\StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs,
        array $coverFiles,
        array $queuedCoverArt,
    ): void {
        $fsIterator = $fs->listContents(Entity\StationMedia::DIR_FOLDER_COVERS, true)->filter(
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
            if (isset($queuedCoverArt[$folderHash])) {
                continue;
            }

            $message = new ProcessCoverArtMessage();
            $message->storage_location_id = $storageLocation->getIdRequired();
            $message->path = $coverFile[StorageAttributes::ATTRIBUTE_PATH];
            $message->folder_hash = $folderHash;

            $this->messageBus->dispatch($message);
        }
    }

    private function processExistingMediaRows(
        Entity\StorageLocation $storageLocation,
        array $queuedMediaUpdates,
        array &$musicFiles,
        array &$stats
    ): void {
        $existingMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT sm.id, sm.path, sm.mtime, sm.unique_id
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        foreach ($existingMediaQuery->toIterable([], AbstractQuery::HYDRATE_ARRAY) as $mediaRow) {
            // Check if media file still exists.
            $path = $mediaRow['path'];
            $pathHash = md5($path);

            if (isset($musicFiles[$pathHash])) {
                if (isset($queuedMediaUpdates[$mediaRow['id']])) {
                    $stats['already_queued']++;
                    unset($musicFiles[$pathHash]);
                    continue;
                }

                $fileInfo = $musicFiles[$pathHash];
                $mtime = $fileInfo[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? 0;

                if (
                    empty($mediaRow['unique_id'])
                    || Entity\StationMedia::needsReprocessing($mtime, $mediaRow['mtime'] ?? 0)
                ) {
                    $message = new ReprocessMediaMessage();
                    $message->storage_location_id = $storageLocation->getIdRequired();
                    $message->media_id = $mediaRow['id'];
                    $message->force = empty($mediaRow['unique_id']);

                    $this->messageBus->dispatch($message);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }

                unset($musicFiles[$pathHash]);
            } else {
                $media = $this->em->find(Entity\StationMedia::class, $mediaRow['id']);
                if ($media instanceof Entity\StationMedia) {
                    $this->mediaRepo->remove($media);
                }

                $stats['deleted']++;
            }
        }

        $this->em->clear();
    }

    private function processUnprocessableMediaRows(
        Entity\StorageLocation $storageLocation,
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

                if (Entity\UnprocessableMedia::needsReprocessing($mtime, $unprocessableRow['mtime'] ?? 0)) {
                    $message = new AddNewMediaMessage();
                    $message->storage_location_id = $storageLocation->getIdRequired();
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

    private function processNewFiles(
        Entity\StorageLocation $storageLocation,
        array $queuedNewFiles,
        array $musicFiles,
        array &$stats
    ): void {
        foreach ($musicFiles as $pathHash => $newMusicFile) {
            $path = $newMusicFile[StorageAttributes::ATTRIBUTE_PATH];

            if (isset($queuedNewFiles[$pathHash])) {
                $stats['already_queued']++;
            } else {
                $message = new AddNewMediaMessage();
                $message->storage_location_id = $storageLocation->getIdRequired();
                $message->path = $path;

                $this->messageBus->dispatch($message);

                $stats['created']++;
            }
        }
    }
}
