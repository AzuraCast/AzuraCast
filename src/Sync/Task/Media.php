<?php
namespace App\Sync\Task;

use App\Radio\Filesystem;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

class Media extends TaskAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /** @var Logger */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param Filesystem $filesystem
     * @param Logger $logger
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(EntityManager $em, Filesystem $filesystem, Logger $logger)
    {
        $this->em = $em;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function run($force = false)
    {
        $station_repo = $this->em->getRepository(Entity\Station::class);
        $stations = $station_repo->findAll();

        foreach ($stations as $station) {
            $this->importMusic($station);
        }
    }

    public function importMusic(Entity\Station $station)
    {
        $fs = $this->filesystem->getForStation($station);

        $stats = [
            'total_files' => 0,
            'updated' => 0,
            'created' => 0,
            'deleted' => 0,
        ];

        $music_files = [];
        foreach($fs->listContents('media://', true) as $file) {
            if ('file' !== $file['type']) {
                continue;
            }

            $path_short = $file['path'];

            $path_hash = md5($path_short);
            $music_files[$path_hash] = $path_short;
        }

        $stats['total_files'] = count($music_files);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        $existing_media_q = $this->em->createQuery('SELECT sm FROM '.Entity\StationMedia::class.' sm WHERE sm.station_id = :station_id')
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

                $media_repo->processMedia($media_row, $force_reprocess);

                unset($music_files[$path_hash]);
                $stats['updated']++;
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
        $i = 0;

        foreach ($music_files as $new_file_path) {
            $media_repo->getOrCreate($station, $new_file_path);
            $stats['created']++;

            if ($i % $records_per_batch === 0) {
                $this->_flushAndClearRecords();
            }

            ++$i;
        }

        $this->_flushAndClearRecords();

        $this->logger->debug(sprintf('Media processed for station "%s".', $station->getName()), $stats);
    }

    /**
     * Flush the Doctrine Entity Manager and clear associated records to save memory space.
     */
    protected function _flushAndClearRecords()
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
