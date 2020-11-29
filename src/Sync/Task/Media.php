<?php

namespace App\Sync\Task;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Media\MimeType;
use App\Message;
use App\MessageQueue\QueueManager;
use App\Radio\Quota;
use Aws\S3\Exception\S3Exception;
use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Jhofm\FlysystemIterator\Filter\FilterFactory;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

class Media extends AbstractTask
{
    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected FilesystemManager $filesystem;

    protected MessageBus $messageBus;

    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        FilesystemManager $filesystem,
        MessageBus $messageBus,
        QueueManager $queueManager
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->storageLocationRepo = $storageLocationRepo;
        $this->mediaRepo = $mediaRepo;
        $this->filesystem = $filesystem;
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
        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT sl 
            FROM App\Entity\StorageLocation sl 
            WHERE sl.type = :type')
            ->setParameter('type', Entity\StorageLocation::TYPE_STATION_MEDIA);

        $storageLocations = SimpleBatchIteratorAggregate::fromQuery($query, 1);

        foreach ($storageLocations as $storageLocation) {
            /** @var Entity\StorageLocation $storageLocation */
            $this->logger->info(sprintf(
                'Processing media for storage location %s...',
                (string)$storageLocation
            ));

            $this->importMusic($storageLocation);
            gc_collect_cycles();
        }
    }

    public function importMusic(Entity\StorageLocation $storageLocation): void
    {
        $adapter = $storageLocation->getStorageAdapter();
        $fs = $this->filesystem->getFilesystemForAdapter($adapter, false);

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

        $music_files = [];
        $total_size = BigInteger::zero();

        try {
            $fsIterator = $fs->createIterator('/', [
                Options::OPTION_IS_RECURSIVE => true,
                Options::OPTION_FILTER => FilterFactory::isFile(),
            ]);
        } catch (S3Exception $e) {
            $this->logger->error(sprintf('S3 Error for Storage Space %s', (string)$storageLocation), [
                'exception' => $e,
            ]);
            return;
        }

        $protectedPaths = [
            Entity\StationMedia::DIR_ALBUM_ART,
            Entity\StationMedia::DIR_WAVEFORMS,
        ];

        foreach ($fsIterator as $file) {
            foreach ($protectedPaths as $protectedPath) {
                if (0 === strpos($file['path'], $protectedPath)) {
                    continue 2;
                }
            }

            if (!empty($file['size'])) {
                $total_size = $total_size->plus($file['size']);
            }

            $path_hash = md5($file['path']);
            $music_files[$path_hash] = $file;
        }

        $storageLocation->setStorageUsed($total_size);
        $this->em->persist($storageLocation);

        $stats['total_size'] = $total_size . ' (' . Quota::getReadableSize($total_size) . ')';
        $stats['total_files'] = count($music_files);

        // Clear existing queue.
        $this->queueManager->clearQueue(QueueManager::QUEUE_MEDIA);

        // Check queue for existing pending processing entries.
        $existingMediaQuery = $this->em->createQuery(/** @lang DQL */ 'SELECT sm
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation')
            ->setParameter('storageLocation', $storageLocation);

        $iterator = SimpleBatchIteratorAggregate::fromQuery($existingMediaQuery, 10);

        foreach ($iterator as $media_row) {
            /** @var Entity\StationMedia $media_row */

            // Check if media file still exists.
            $path_hash = md5($media_row->getPath());

            if (isset($music_files[$path_hash])) {
                $force_reprocess = false;
                if (empty($media_row->getUniqueId())) {
                    $media_row->generateUniqueId();
                    $force_reprocess = true;
                }

                $file_info = $music_files[$path_hash];
                if ($force_reprocess || $media_row->needsReprocessing($file_info['timestamp'])) {
                    $message = new Message\ReprocessMediaMessage();
                    $message->media_id = $media_row->getId();
                    $message->force = $force_reprocess;

                    $this->messageBus->dispatch($message);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }

                unset($music_files[$path_hash]);
            } else {
                $this->mediaRepo->remove($media_row);

                $stats['deleted']++;
            }
        }

        // Create files that do not currently exist.
        foreach ($music_files as $path_hash => $new_music_file) {
            if (!MimeType::isPathProcessable($new_music_file['path'])) {
                $stats['not_processable']++;
                continue;
            }

            $message = new Message\AddNewMediaMessage();
            $message->storage_location_id = $storageLocation->getId();
            $message->path = $new_music_file['path'];

            $this->messageBus->dispatch($message);

            $stats['created']++;
        }

        $this->logger->debug(sprintf('Media processed for "%s".', (string)$storageLocation), $stats);
    }

    public function importPlaylists(Entity\Station $station): void
    {
        $fs = $this->filesystem->getForStation($station, false);

        // Create a lookup cache of all valid imported media.
        $media_lookup = [];
        foreach ($station->getMedia() as $media) {
            /** @var Entity\StationMedia $media */
            $media_path = $fs->getFullPath($media->getPathUri());
            $media_hash = md5($media_path);

            $media_lookup[$media_hash] = $media;
        }

        // Iterate through playlists.
        $playlist_files_raw = $fs->createIterator(
            FilesystemManager::PREFIX_PLAYLISTS . '://',
            [
                'filter' => FilterFactory::pathMatchesRegex('/^.+\.(m3u|pls)$/i'),
            ]
        );

        foreach ($playlist_files_raw as $playlist_file) {
            // Create new StationPlaylist record.
            $record = new Entity\StationPlaylist($station);

            $playlist_file_path = $fs->getFullPath(
                FilesystemManager::PREFIX_PLAYLISTS . '://' . $playlist_file['path']
            );

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
