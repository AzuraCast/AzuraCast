<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Media\MimeType;
use App\Message;
use App\MessageQueue\QueueManagerInterface;
use App\Radio\Quota;
use Azura\Files\Attributes\FileAttributes;
use Brick\Math\BigInteger;
use Doctrine\ORM\Query;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

class CheckMediaTask extends AbstractTask
{
    public function __construct(
        protected Entity\Repository\StationMediaRepository $mediaRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo,
        protected MessageBus $messageBus,
        protected QueueManagerInterface $queueManager,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\ReprocessMediaMessage) {
            $mediaRow = $this->em->find(Entity\StationMedia::class, $message->media_id);

            if ($mediaRow instanceof Entity\StationMedia) {
                $this->mediaRepo->processMedia($mediaRow, $message->force);
                $this->em->flush();
            }
        } elseif ($message instanceof Message\AddNewMediaMessage) {
            $storageLocation = $this->em->find(Entity\StorageLocation::class, $message->storage_location_id);

            if ($storageLocation instanceof Entity\StorageLocation) {
                $this->mediaRepo->getOrCreate($storageLocation, $message->path);
            }
        }
    }

    public function run(bool $force = false): void
    {
        $storageLocations = $this->iterateStorageLocations(Entity\StorageLocation::TYPE_STATION_MEDIA);

        foreach ($storageLocations as $storageLocation) {
            $this->logger->info(
                sprintf(
                    'Processing media for storage location %s...',
                    (string)$storageLocation
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
            'not_processable' => 0,
        ];

        $musicFiles = [];

        $total_size = BigInteger::zero();

        try {
            $fsIterator = $fs->listContents('/', true)->filter(
                function (StorageAttributes $attrs) {
                    return ($attrs->isFile()
                        && !str_starts_with($attrs->path(), Entity\StationMedia::DIR_ALBUM_ART)
                        && !str_starts_with($attrs->path(), Entity\StationMedia::DIR_WAVEFORMS));
                }
            );
        } catch (FilesystemException $e) {
            $this->logger->error(
                sprintf('Flysystem Error for Storage Space %s', (string)$storageLocation),
                [
                    'exception' => $e,
                ]
            );
            return;
        }

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

            $pathHash = md5($file->path());
            $musicFiles[$pathHash] = [
                StorageAttributes::ATTRIBUTE_PATH => $file->path(),
                StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $file->lastModified(),
            ];
        }

        $storageLocation->setStorageUsed($total_size);
        $this->em->persist($storageLocation);
        $this->em->flush();

        $stats['total_size'] = $total_size . ' (' . Quota::getReadableSize($total_size) . ')';
        $stats['total_files'] = count($musicFiles);

        // Check queue for existing pending processing entries.
        $queuedMediaUpdates = [];
        $queuedNewFiles = [];

        foreach ($this->queueManager->getMessagesInTransport(QueueManagerInterface::QUEUE_MEDIA) as $message) {
            if ($message instanceof Message\ReprocessMediaMessage) {
                $queuedMediaUpdates[$message->media_id] = true;
            } elseif (
                $message instanceof Message\AddNewMediaMessage
                && $message->storage_location_id === $storageLocation->getId()
            ) {
                $queuedNewFiles[$message->path] = true;
            }
        }

        // Check queue for existing pending processing entries.
        $this->processExistingMediaRows($storageLocation, $queuedMediaUpdates, $musicFiles, $stats);

        gc_collect_cycles();

        $storageLocation = $this->em->refetch($storageLocation);

        // Loop through currently unprocessable media.
        $this->processUnprocessableMediaRows($storageLocation, $musicFiles, $stats);

        gc_collect_cycles();

        $storageLocation = $this->em->refetch($storageLocation);

        $this->processNewFiles($storageLocation, $queuedNewFiles, $musicFiles, $stats);

        $this->logger->debug(sprintf('Media processed for "%s".', (string)$storageLocation), $stats);
    }

    protected function processExistingMediaRows(
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

        foreach ($existingMediaQuery->toIterable([], Query::HYDRATE_ARRAY) as $mediaRow) {
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
                    $message = new Message\ReprocessMediaMessage();
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
                    $this->mediaRepo->remove($media, false);
                }

                $stats['deleted']++;
            }
        }
    }

    protected function processUnprocessableMediaRows(
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

        $unprocessableRecords = $unprocessableMediaQuery->toIterable([], Query::HYDRATE_ARRAY);

        foreach ($unprocessableRecords as $unprocessableRow) {
            $pathHash = md5($unprocessableRow['path']);

            if (isset($musicFiles[$pathHash])) {
                $fileInfo = $musicFiles[$pathHash];
                $mtime = $fileInfo[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? 0;

                if (Entity\UnprocessableMedia::needsReprocessing($mtime, $unprocessableRow['mtime'] ?? 0)) {
                    $message = new Message\AddNewMediaMessage();
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
    }

    protected function processNewFiles(
        Entity\StorageLocation $storageLocation,
        array $queuedNewFiles,
        array $musicFiles,
        array &$stats
    ): void {
        foreach ($musicFiles as $newMusicFile) {
            $path = $newMusicFile[StorageAttributes::ATTRIBUTE_PATH];

            if (!MimeType::isPathProcessable($path)) {
                $mimeType = MimeType::getMimeTypeFromPath($path);

                $this->unprocessableMediaRepo->setForPath(
                    $storageLocation,
                    $path,
                    sprintf('MIME type "%s" is not processable.', $mimeType)
                );

                $stats['not_processable']++;
            }

            if (isset($queuedNewFiles[$path])) {
                $stats['already_queued']++;
            } else {
                $message = new Message\AddNewMediaMessage();
                $message->storage_location_id = $storageLocation->getIdRequired();
                $message->path = $path;

                $this->messageBus->dispatch($message);

                $stats['created']++;
            }
        }
    }
}
