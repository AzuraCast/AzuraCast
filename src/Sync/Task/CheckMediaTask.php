<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Media\MimeType;
use App\Message;
use App\MessageQueue\QueueManager;
use App\Radio\Quota;
use Aws\S3\Exception\S3Exception;
use Brick\Math\BigInteger;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

class CheckMediaTask extends AbstractTask
{
    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo;

    protected MessageBus $messageBus;

    protected QueueManager $queueManager;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo,
        MessageBus $messageBus,
        QueueManager $queueManager
    ) {
        parent::__construct($em, $logger);

        $this->storageLocationRepo = $storageLocationRepo;
        $this->mediaRepo = $mediaRepo;
        $this->unprocessableMediaRepo = $unprocessableMediaRepo;
        $this->messageBus = $messageBus;
        $this->queueManager = $queueManager;
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
        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT sl
                FROM App\Entity\StorageLocation sl
                WHERE sl.type = :type
            DQL
        )->setParameter('type', Entity\StorageLocation::TYPE_STATION_MEDIA);

        $storageLocations = SimpleBatchIteratorAggregate::fromQuery($query, 1);

        foreach ($storageLocations as $storageLocation) {
            /** @var Entity\StorageLocation $storageLocation */
            $this->logger->info(
                sprintf(
                    'Processing media for storage location %s...',
                    (string)$storageLocation
                )
            );

            $this->importMusic($storageLocation);
            gc_collect_cycles();
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

        /** @var StorageAttributes[] $musicFiles */
        $musicFiles = [];

        $total_size = BigInteger::zero();

        try {
            $fsIterator = $fs->listContents('/', true)->filter(
                function (StorageAttributes $attrs) {
                    return $attrs->isFile();
                }
            );
        } catch (S3Exception $e) {
            $this->logger->error(
                sprintf('S3 Error for Storage Space %s', (string)$storageLocation),
                [
                    'exception' => $e,
                ]
            );
            return;
        }

        $protectedPaths = [
            Entity\StationMedia::DIR_ALBUM_ART,
            Entity\StationMedia::DIR_WAVEFORMS,
        ];

        /** @var StorageAttributes $file */
        foreach ($fsIterator as $file) {
            foreach ($protectedPaths as $protectedPath) {
                if (0 === strpos($file->path(), $protectedPath)) {
                    continue 2;
                }
            }

            $size = $fs->fileSize($file->path());
            $total_size = $total_size->plus($size);

            $pathHash = md5($file->path());
            $musicFiles[$pathHash] = $file;
        }

        $storageLocation->setStorageUsed($total_size);
        $this->em->persist($storageLocation);

        $stats['total_size'] = $total_size . ' (' . Quota::getReadableSize($total_size) . ')';
        $stats['total_files'] = count($musicFiles);

        // Check queue for existing pending processing entries.
        $queuedMediaUpdates = [];
        $queuedNewFiles = [];

        foreach ($this->queueManager->getMessagesInTransport(QueueManager::QUEUE_MEDIA) as $message) {
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
        $existingMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT sm FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        $iterator = SimpleBatchIteratorAggregate::fromQuery($existingMediaQuery, 10);

        foreach ($iterator as $media_row) {
            /** @var Entity\StationMedia $media_row */

            // Check if media file still exists.
            $pathHash = md5($media_row->getPath());

            if (isset($musicFiles[$pathHash])) {
                $force_reprocess = false;
                if (empty($media_row->getUniqueId())) {
                    $media_row->generateUniqueId();
                    $force_reprocess = true;
                }

                $fileInfo = $musicFiles[$pathHash];

                if (isset($queuedMediaUpdates[$media_row->getId()])) {
                    $stats['already_queued']++;
                } elseif ($force_reprocess || $media_row->needsReprocessing($fileInfo->lastModified())) {
                    $message = new Message\ReprocessMediaMessage();
                    $message->media_id = $media_row->getId();
                    $message->force = $force_reprocess;

                    $this->messageBus->dispatch($message);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }

                unset($musicFiles[$pathHash]);
            } else {
                $this->mediaRepo->remove($media_row, false);

                $stats['deleted']++;
            }
        }

        // Loop through currently unprocessable media.
        $unprocessableMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT upm FROM App\Entity\UnprocessableMedia upm
                WHERE upm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        $iterator = SimpleBatchIteratorAggregate::fromQuery($unprocessableMediaQuery, 10);

        foreach ($iterator as $unprocessableRow) {
            /** @var Entity\UnprocessableMedia $unprocessableRow */
            $pathHash = md5($unprocessableRow->getPath());

            if (isset($musicFiles[$pathHash])) {
                $fileInfo = $musicFiles[$pathHash];

                if ($unprocessableRow->needsReprocessing($fileInfo->lastModified())) {
                    $message = new Message\AddNewMediaMessage();
                    $message->storage_location_id = $storageLocation->getId();
                    $message->path = $fileInfo->path();

                    $this->messageBus->dispatch($message);

                    $stats['updated']++;
                } else {
                    $stats['not_processable']++;
                }

                unset($musicFiles[$pathHash]);
            } else {
                $this->unprocessableMediaRepo->clearForPath($storageLocation, $unprocessableRow->getPath());
            }
        }

        $storageLocation = $this->em->refetch($storageLocation);

        // Create files that do not currently exist.
        foreach ($musicFiles as $pathHash => $newMusicFile) {
            $path = $newMusicFile->path();
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
                $message->storage_location_id = $storageLocation->getId();
                $message->path = $path;

                $this->messageBus->dispatch($message);

                $stats['created']++;
            }
        }

        $this->logger->debug(sprintf('Media processed for "%s".', (string)$storageLocation), $stats);
    }

    public function importPlaylists(Entity\Station $station): void
    {
        $fsStation = new StationFilesystems($station);

        $fsMedia = $fsStation->getMediaFilesystem();

        // Skip playlist importing for remote filesystems.
        if (!$fsMedia->isLocal()) {
            return;
        }

        $fsPlaylists = $fsStation->getPlaylistsFilesystem();

        // Create a lookup cache of all valid imported media.
        $media_lookup = [];
        foreach ($station->getMedia() as $media) {
            /** @var Entity\StationMedia $media */
            $media_path = $fsMedia->getLocalPath($media->getPath());
            $media_hash = md5($media_path);

            $media_lookup[$media_hash] = $media;
        }

        // Iterate through playlists.
        $playlist_files_raw = $fsPlaylists->listContents('/', true)->filter(
            function (StorageAttributes $attrs) {
                return preg_match('/^.+\.(m3u|pls)$/i', $attrs->path()) > 0;
            }
        );

        foreach ($playlist_files_raw as $playlist_file) {
            /** @var StorageAttributes $playlist_file */

            // Create new StationPlaylist record.
            $record = new Entity\StationPlaylist($station);

            $playlist_file_path = $fsPlaylists->getLocalPath($playlist_file->path());

            $path_parts = pathinfo($playlist_file_path);
            $playlist_name = str_replace('playlist_', '', $path_parts['filename']);
            $record->setName($playlist_name);

            $playlist_file = file_get_contents($playlist_file_path);
            $playlist_lines = explode("\n", $playlist_file);
            $this->em->persist($record);

            foreach ($playlist_lines as $line_raw) {
                $line = trim($line_raw);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                if (file_exists($line)) {
                    $line_hash = md5($line);
                    if (isset($media_lookup[$line_hash])) {
                        /** @var Entity\StationMedia $media_record */
                        $media_record = $media_lookup[$line_hash];

                        $spm = new Entity\StationPlaylistMedia($record, $media_record);
                        $this->em->persist($spm);
                    }
                }
            }

            @unlink($playlist_file_path);
        }

        $this->em->flush();
    }
}
