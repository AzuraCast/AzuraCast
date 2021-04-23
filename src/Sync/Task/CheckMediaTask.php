<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Media\MimeType;
use App\Message;
use App\MessageQueue\QueueManager;
use App\Radio\Quota;
use Brick\Math\BigInteger;
use Doctrine\ORM\Query;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

class CheckMediaTask extends AbstractTask
{
    public function __construct(
        protected Entity\Repository\StationMediaRepository $mediaRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo,
        protected MessageBus $messageBus,
        protected QueueManager $queueManager,
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
                        && 0 !== strpos($attrs->path(), Entity\StationMedia::DIR_ALBUM_ART)
                        && 0 !== strpos($attrs->path(), Entity\StationMedia::DIR_WAVEFORMS));
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

        /** @var StorageAttributes $file */
        foreach ($fsIterator as $file) {
            $size = $fs->fileSize($file->path());
            $total_size = $total_size->plus($size);

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

        $mediaRecords = $existingMediaQuery->toIterable([], Query::HYDRATE_ARRAY);

        foreach ($mediaRecords as $mediaRow) {
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
                $mtime = $fileInfo[StorageAttributes::ATTRIBUTE_LAST_MODIFIED];

                if (
                    empty($mediaRow['unique_id'])
                    || Entity\StationMedia::needsReprocessing($mtime, $mediaRow['mtime'])
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
                $this->mediaRepo->remove(
                    $this->em->find(Entity\StationMedia::class, $mediaRow['id']),
                    false
                );

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
                $mtime = $fileInfo[StorageAttributes::ATTRIBUTE_LAST_MODIFIED];

                if (Entity\UnprocessableMedia::needsReprocessing($mtime, $unprocessableRow['mtime'])) {
                    $message = new Message\AddNewMediaMessage();
                    $message->storage_location_id = $storageLocation->getId();
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
                $message->storage_location_id = $storageLocation->getId();
                $message->path = $path;

                $this->messageBus->dispatch($message);

                $stats['created']++;
            }
        }
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

        /** @var StorageAttributes $playlist_file */
        foreach ($playlist_files_raw as $playlist_file) {
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
