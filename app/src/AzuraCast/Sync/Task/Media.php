<?php
namespace AzuraCast\Sync\Task;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Entity;

class Media extends TaskAbstract
{
    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function run($force = false)
    {
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        foreach ($stations as $station) {
            $this->importMusic($station);
        }
    }

    public function importMusic(Entity\Station $station)
    {
        $base_dir = $station->getRadioMediaDir();
        if (empty($base_dir)) {
            return;
        }

        $music_files_raw = $this->globDirectory($base_dir);
        $music_files = [];

        foreach ($music_files_raw as $music_file_path) {
            $path_short = str_replace($base_dir . '/', '', $music_file_path);

            $path_hash = md5($path_short);
            $music_files[$path_hash] = $path_short;
        }

        /** @var Entity\Repository\SongRepository $song_repo */
        $song_repo = $this->em->getRepository(Entity\Song::class);

        $existing_media_q = $this->em->createQuery('SELECT sm FROM Entity\StationMedia sm WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId());
        $existing_media = $existing_media_q->iterate();

        $i = 0;

        foreach ($existing_media as $media_row_iteration) {
            /** @var Entity\StationMedia $media_row */
            $media_row = $media_row_iteration[0];

            // Check if media file still exists.
            $full_path = $base_dir . '/' . $media_row->getPath();

            if (file_exists($full_path)) {

                $force_reprocess = false;
                if (empty($media_row->getUniqueId())) {
                    $media_row->generateUniqueId();
                    $force_reprocess = true;
                }

                // Check for modifications.
                $song_info = $media_row->loadFromFile($force_reprocess);

                if (is_array($song_info)) {
                    $media_row->setSong($song_repo->getOrCreate($song_info));
                }

                $this->em->persist($media_row);

                $path_hash = md5($media_row->getPath());
                unset($music_files[$path_hash]);
            } else {
                // Delete the now-nonexistent media item.
                $this->em->remove($media_row);
            }

            // Batch processing
            if ($i % 20 === 0) {
                $this->_flushAndClearRecords();
            }

            ++$i;
        }

        $this->_flushAndClearRecords();

        // Create files that do not currently exist.
        $i = 0;

        foreach ($music_files as $new_file_path) {
            $media_row = new Entity\StationMedia($station, $new_file_path);

            $song_info = $media_row->loadFromFile();
            if (is_array($song_info)) {
                $media_row->setSong($song_repo->getOrCreate($song_info));
            }

            $this->em->persist($media_row);

            if ($i % 20 === 0) {
                $this->_flushAndClearRecords();
            }

            ++$i;
        }

        $this->_flushAndClearRecords();
    }

    /**
     * Flush the Doctrine Entity Manager and clear associated records to save memory space.
     */
    protected function _flushAndClearRecords()
    {
        $this->em->flush();

        try {
            $this->em->clear(Entity\StationMedia::class);
            $this->em->clear(Entity\StationMediaArt::class);
            $this->em->clear(Entity\Song::class);
        } catch (MappingException $e) {}
    }

    public function importPlaylists(Entity\Station $station)
    {
        $base_dir = $station->getRadioPlaylistsDir();
        if (empty($base_dir)) {
            return;
        }

        // Create a lookup cache of all valid imported media.
        $media_lookup = [];
        foreach ($station->getMedia() as $media) {
            /** @var Entity\StationMedia $media */
            $media_path = $media->getFullPath();
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
        $directory = new \RecursiveDirectoryIterator($base_dir);
        $iterator = new \RecursiveIteratorIterator($directory);

        $files = [];

        if ($regex_pattern !== null) {
            $regex = new \RegexIterator($iterator, $regex_pattern, \RecursiveRegexIterator::GET_MATCH);
            foreach($regex as $path_match) {
                $files[] = $path_match[0];
            }
        } else {
            foreach ($iterator as $file) {
                if ($file->isDir()){
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}