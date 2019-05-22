<?php
namespace App\Sync\Task;

use App\MessageQueue;
use App\Message;
use App\Radio\Filesystem;
use App\Radio\Quota;
use Brick\Math\BigInteger;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

class Media extends AbstractTask
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var MessageQueue */
    protected $message_queue;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param Filesystem $filesystem
     * @param MessageQueue $message_queue
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        Filesystem $filesystem,
        MessageQueue $message_queue
    ) {
        parent::__construct($em, $logger);

        $this->filesystem = $filesystem;
        $this->message_queue = $message_queue;
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     * @throws MappingException
     */
    public function __invoke(Message\AbstractMessage $message)
    {
        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        try {
            if ($message instanceof Message\ReprocessMediaMessage) {
                $media_row = $media_repo->find($message->media_id);

                if ($media_row instanceof Entity\StationMedia) {
                    $media_repo->processMedia($media_row, $message->force);

                    $this->em->flush($media_row);
                }
            } else if ($message instanceof Message\AddNewMediaMessage) {
                $station = $this->em->find(Entity\Station::class, $message->station_id);

                if ($station instanceof Entity\Station) {
                    $media_repo->getOrCreate($station, $message->path);
                }
            }
        } finally {
            $this->em->clear();
        }
    }

    /**
     * @inheritdoc
     */
    public function run($force = false): void
    {
        $station_repo = $this->em->getRepository(Entity\Station::class);
        $stations = $station_repo->findAll();

        foreach ($stations as $station) {
            $this->importMusic($station);
            gc_collect_cycles();
        }
    }

    public function importMusic(Entity\Station $station)
    {
        $fs = $this->filesystem->getForStation($station);
        $fs->flushAllCaches();

        $stats = [
            'total_size' => '0',
            'total_files' => 0,
            'unchanged' => 0,
            'updated' => 0,
            'created' => 0,
            'deleted' => 0,
        ];

        $music_files = [];
        $total_size = BigInteger::zero();

        foreach($fs->listContents('media://', true) as $file) {
            if (!empty($file['size'])) {
                $total_size = $total_size->plus($file['size']);
            }

            if ('file' !== $file['type']) {
                continue;
            }

            $path_hash = md5($file['path']);
            $music_files[$path_hash] = $file;
        }

        $station->setStorageUsed($total_size);
        $this->em->persist($station);

        $stats['total_size'] = $total_size.' ('.Quota::getReadableSize($total_size).')';
        $stats['total_files'] = count($music_files);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        $existing_media_q = $this->em->createQuery(/** @lang DQL */'SELECT 
            sm 
            FROM App\Entity\StationMedia sm 
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId());
        $existing_media = $existing_media_q->iterate();

        $records_per_batch = 10;
        $i = 0;

        foreach ($existing_media as $media_row_iteration) {
            /** @var Entity\StationMedia $media_row */
            $media_row = $media_row_iteration[0];

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

                    $this->message_queue->produce($message);

                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }

                unset($music_files[$path_hash]);
            } else {
                // Delete the now-nonexistent media item.
                $this->em->remove($media_row);

                $stats['deleted']++;
            }

            // Batch processing
            if ($i % $records_per_batch === 0) {
                $this->_flushAndClearRecords();
            }

            ++$i;
        }

        $this->_flushAndClearRecords();

        // Create files that do not currently exist.
        foreach ($music_files as $path_hash => $new_music_file) {
            $message = new Message\AddNewMediaMessage;
            $message->station_id = $station->getId();
            $message->path = $new_music_file['path'];

            $this->message_queue->produce($message);

            $stats['created']++;
        }

        $this->_flushAndClearRecords();
        $fs->flushAllCaches(true);

        $this->logger->debug(sprintf('Media processed for station "%s".', $station->getName()), $stats);
    }

    /**
     * Flush the Doctrine Entity Manager and clear associated records to save memory space.
     */
    protected function _flushAndClearRecords(): void
    {
        $this->em->flush();

        try {
            $this->em->clear(Entity\StationMedia::class);
            $this->em->clear(Entity\Song::class);
        } catch (MappingException $e) {}
    }

    public function importPlaylists(Entity\Station $station)
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
                if (empty($line) || $line[0] === '#') {
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

    public function globDirectory($base_dir, $regex_pattern = null)
    {
        /** @var Finder $finder */
        $finder = new Finder();
        $finder = $finder->files()->in($base_dir);

        if ($regex_pattern !== null) {
            $finder = $finder->name($regex_pattern);
        }

        $files = [];
        foreach($finder as $file) {
            $files[] = $file->getPathname();
        }
        return $files;
    }
}
