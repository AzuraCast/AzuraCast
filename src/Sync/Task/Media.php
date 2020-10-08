<?php
namespace App\Sync\Task;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Message;
use App\MessageQueue\QueueManager;
use App\Radio\Quota;
use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Jhofm\FlysystemIterator\Filter\FilterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\MessageBus;

class Media extends AbstractTask
{
    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Filesystem $filesystem;

    protected MessageBus $messageBus;

    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Filesystem $filesystem,
        MessageBus $messageBus,
        QueueManager $queueManager
    ) {
        parent::__construct($em, $settingsRepo, $logger);

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
    public function __invoke(Message\AbstractMessage $message)
    {
        if ($message instanceof Message\ReprocessMediaMessage) {
            $media_row = $this->em->find(Entity\StationMedia::class, $message->media_id);

            if ($media_row instanceof Entity\StationMedia) {
                $this->mediaRepo->processMedia($media_row, $message->force);
                $this->em->flush();
            }
        } elseif ($message instanceof Message\AddNewMediaMessage) {
            $station = $this->em->find(Entity\Station::class, $message->station_id);

            if ($station instanceof Entity\Station) {
                $this->mediaRepo->getOrCreate($station, $message->path);
            }
        }
    }

    public function run(bool $force = false): void
    {
        $stations = SimpleBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(/** @lang DQL */ 'SELECT s FROM App\Entity\Station s'),
            1
        );

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $this->logger->info('Processing media for station...', [
                'station' => $station->getName(),
            ]);

            $this->importMusic($station);
            gc_collect_cycles();
        }
    }

    public function importMusic(Entity\Station $station): void
    {
        $fs = $this->filesystem->getForStation($station, false);

        $stats = [
            'total_size' => '0',
            'total_files' => 0,
            'already_queued' => 0,
            'unchanged' => 0,
            'updated' => 0,
            'created' => 0,
            'deleted' => 0,
        ];

        $music_files = [];
        $total_size = BigInteger::zero();

        $fsIterator = $fs->createIterator(Filesystem::PREFIX_MEDIA . '://', [
            'filter' => FilterFactory::isFile(),
        ]);

        foreach ($fsIterator as $file) {
            if (!empty($file['size'])) {
                $total_size = $total_size->plus($file['size']);
            }

            $path_hash = md5($file['path']);
            $music_files[$path_hash] = $file;
        }

        $station->setStorageUsed($total_size);
        $this->em->persist($station);

        $stats['total_size'] = $total_size . ' (' . Quota::getReadableSize($total_size) . ')';
        $stats['total_files'] = count($music_files);

        // Clear existing queue.
        $this->queueManager->clearQueue(QueueManager::QUEUE_MEDIA);

        // Check queue for existing pending processing entries.
        $existingMediaQuery = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            sm 
            FROM App\Entity\StationMedia sm 
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId());

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
                    $message = new Message\ReprocessMediaMessage;
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
            $message = new Message\AddNewMediaMessage;
            $message->station_id = $station->getId();
            $message->path = $new_music_file['path'];

            $this->messageBus->dispatch($message);

            $stats['created']++;
        }

        $this->logger->debug(sprintf('Media processed for station "%s".', $station->getName()), $stats);
    }

    public function importPlaylists(Entity\Station $station): void
    {
        $fs = $this->filesystem->getForStation($station);

        $base_dir = $station->getRadioPlaylistsDir();
        if (empty($base_dir)) {
            return;
        }

        // Create a lookup cache of all valid imported media.
        $media_lookup = [];
        foreach ($station->getMedia() as $media) {
            /** @var Entity\StationMedia $media */
            $media_path = $fs->getFullPath($media->getPathUri());
            $media_hash = md5($media_path);

            $media_lookup[$media_hash] = $media;
        }

        // Iterate through playlists.
        $playlist_files_raw = $this->globDirectory($base_dir, '/^.+\.(m3u|pls)$/i');

        foreach ($playlist_files_raw as $playlist_file_path) {
            // Create new StationPlaylist record.
            $record = new Entity\StationPlaylist($station);

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

    public function globDirectory($base_dir, $regex_pattern = null): array
    {
        $finder = new Finder();
        $finder = $finder->files()->in($base_dir);

        if ($regex_pattern !== null) {
            $finder = $finder->name($regex_pattern);
        }

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getPathname();
        }
        return $files;
    }
}
