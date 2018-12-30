<?php
namespace App\Entity\Repository;

use App\Entity;
use App\Radio\Filesystem;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;

class StationMediaRepository extends Repository
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var SongRepository */
    protected $song_repo;

    public function __construct($em, Mapping\ClassMetadata $class, Filesystem $filesystem)
    {
        parent::__construct($em, $class);

        $this->filesystem = $filesystem;
        $this->song_repo = $this->_em->getRepository(Entity\Song::class);
    }

    /**
     * @param Entity\Station $station
     * @return array
     */
    public function getRequestable(Entity\Station $station)
    {
        return $this->_em->createQuery('SELECT sm FROM ' . $this->_entityName . ' sm WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->getId())
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     * @param $artist_name
     * @return array
     */
    public function getByArtist(Entity\Station $station, $artist_name)
    {
        return $this->_em->createQuery('SELECT sm FROM ' . $this->_entityName . ' sm WHERE sm.station_id = :station_id AND sm.artist LIKE :artist ORDER BY sm.title ASC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('artist', $artist_name)
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     * @param $query
     * @return array
     */
    public function search(Entity\Station $station, $query)
    {
        // TODO: Replace this!
        $db = $this->_em->getConnection();
        $table_name = $this->_em->getClassMetadata(__CLASS__)->getTableName();

        $stmt = $db->executeQuery('SELECT sm.* FROM ' . $db->quoteIdentifier($table_name) . ' AS sm WHERE sm.station_id = ? AND CONCAT(sm.title, \' \', sm.artist, \' \', sm.album) LIKE ?',
            [$station->getId(), '%' . addcslashes($query, "%_") . '%']);

        return $stmt->fetchAll();
    }

    /**
     * @param Entity\Station $station
     * @param string $tmp_path
     * @param string $dest
     * @return Entity\StationMedia
     */
    public function uploadFile(Entity\Station $station, $tmp_path, $dest): Entity\StationMedia
    {
        [$dest_prefix, $dest_path] = explode('://', $dest, 2);

        $record = $this->findOneBy([
            'station_id' => $station->getId(),
            'path' => $dest_path,
        ]);

        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia($station, $dest_path);
        }

        $this->loadFromFile($record, $tmp_path);

        $fs = $this->filesystem->getForStation($station);
        $fs->upload($tmp_path, $dest);

        $record->setMtime(time());

        $this->_em->persist($record);
        $this->_em->flush($record);

        return $record;
    }

    /**
     * @param Entity\Station $station
     * @param $path
     * @return Entity\StationMedia
     * @throws \Exception
     */
    public function getOrCreate(Entity\Station $station, $path): Entity\StationMedia
    {
        if (strpos($path, '://') !== false) {
            [$path_prefix, $path] = explode('://', $path, 2);
        }

        $record = $this->findOneBy([
            'station_id' => $station->getId(),
            'path' => $path
        ]);

        $created = false;
        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia($station, $path);
            $created = true;
        }

        $processed = $this->processMedia($record);

        if ($created) {
            $this->_em->persist($record);
            $this->_em->flush($record);
        }

        return $record;
    }

    /**
     * Run media through the "processing" steps: loading from file and setting up any missing metadata.
     *
     * @param Entity\StationMedia $media
     * @param bool $force
     * @return bool Whether reprocessing was required for this file.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \getid3_exception
     */
    public function processMedia(Entity\StationMedia $media, $force = false): bool
    {
        $media_uri = $media->getPathUri();

        $fs = $this->filesystem->getForStation($media->getStation());
        if (!$fs->has($media_uri)) {
            throw new \App\Exception\MediaProcessing(sprintf('Media path "%s" not found.', $media_uri));
        }

        $media_mtime = $fs->getTimestamp($media_uri);

        // No need to update if all of these conditions are true.
        if (!$force && !$media->needsReprocessing($media_mtime)) {
            return false;
        }

        $tmp_uri = $fs->copyToTemp($media_uri);
        $tmp_path = $fs->getFullPath($tmp_uri);

        $this->loadFromFile($media, $tmp_path);

        $fs->delete($tmp_uri);

        $media->setMtime($media_mtime);
        $this->_em->persist($media);

        return true;
    }

    /**
     * Process metadata information from media file.
     *
     * @param Entity\StationMedia $media
     * @param null $file_path
     * @throws \getid3_exception
     */
    public function loadFromFile(Entity\StationMedia $media, $file_path = null): void
    {
        // Load metadata from supported files.
        $id3 = new \getID3();
 
        $id3->option_md5_data = true;
        $id3->option_md5_data_source = true;
        $id3->encoding = 'UTF-8';

        $file_info = $id3->analyze($file_path);

        // Report any errors found by the file analysis to the logs
        if (!empty($file_info['error'])) {

            $media_warning = 'Warning for uploaded media file "'.pathinfo($media->getPath(), PATHINFO_FILENAME).'": '.json_encode($file_info['error']);
            error_log($media_warning);
        }

        // Set playtime length if the analysis was able to determine it
        if ( is_numeric($file_info['playtime_seconds']) ) {
            $media->setLength($file_info['playtime_seconds']);
        }

        $tags_to_set = ['title', 'artist', 'album'];
        if (!empty($file_info['tags'])) {
            foreach ($file_info['tags'] as $tag_type => $tag_data) {
                foreach ($tags_to_set as $tag) {
                    if (!empty($tag_data[$tag][0])) {
                        $media->{'set'.ucfirst($tag)}(mb_convert_encoding($tag_data[$tag][0], "UTF-8"));
                    }
                }

                if (!empty($tag_data['unsynchronized_lyric'][0])) {
                    $media->setLyrics($tag_data['unsynchronized_lyric'][0]);
                }
            }
        }

        if (!empty($file_info['attached_picture'][0])) {
            $picture = $file_info['attached_picture'][0];
            $this->writeAlbumArt($media, $picture['data']);
        } else if (!empty($file_info['comments']['picture'][0])) {
            $picture = $file_info['comments']['picture'][0];
            $this->writeAlbumArt($media, $picture['data']);
        }

        // Attempt to derive title and artist from filename.
        if (empty($media->getTitle())) {
            $filename = pathinfo($media->getPath(), PATHINFO_FILENAME);
            $filename = str_replace('_', ' ', $filename);

            $string_parts = explode('-', $filename);

            // If not normally delimited, return "text" only.
            if (count($string_parts) == 1) {
                $media->setTitle(trim($filename));
                $media->setArtist('');
            } else {
                $media->setTitle(trim(array_pop($string_parts)));
                $media->setArtist(trim(implode('-', $string_parts)));
            }
        }

        $media->setSong($this->song_repo->getOrCreate([
            'artist'    => $media->getArtist(),
            'title'     => $media->getTitle(),
        ]));
    }

    /**
     * Write modified metadata directly to the file as ID3 information.
     *
     * @param Entity\StationMedia $media
     * @return bool
     * @throws \getid3_exception
     */
    public function writeToFile(Entity\StationMedia $media): bool
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        $getID3 = new \getID3;
        $getID3->setOption(['encoding' => 'UTF8']);

        $tmp_uri = $fs->copyToTemp($media->getPathUri());
        $tmp_path = $fs->getFullPath($tmp_uri);

        $tagwriter = new \getid3_writetags;
        $tagwriter->filename = $tmp_path;

        $tagwriter->tagformats = ['id3v1', 'id3v2.3'];
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $tag_data = [
            'title' => [
                $media->getTitle()
            ],
            'artist' => [
                $media->getArtist()
            ],
            'album' => [
                $media->getAlbum()
            ],
            'unsynchronized_lyric' => [
                $media->getLyrics()
            ],
        ];

        $art_path = $media->getArtPath();
        if ($fs->has($art_path)) {
            $tag_data['attached_picture'][0] = [
                'encodingid'     => 0, // ISO-8859-1; 3=UTF8 but only allowed in ID3v2.4
                'description'    => 'cover art',
                'data' => $fs->read($art_path),
                'picturetypeid' => 0x03,
                'mime' => 'image/jpeg',
            ];

            $tag_data['comments']['picture'][0] = $tag_data['attached_picture'][0];
        }

        $tagwriter->tag_data = $tag_data;

        // write tags
        if ($tagwriter->WriteTags()) {
            $media->setMtime(time());

            $fs->updateFromTemp($tmp_uri, $media->getPathUri());
            return true;
        }

        return false;
    }

    /**
     * Crop album art and write the resulting image to storage.
     *
     * @param Entity\StationMedia $media
     * @param string $raw_art_string The raw image data, as would be retrieved from file_get_contents.
     * @return bool
     */
    public function writeAlbumArt(Entity\StationMedia $media, $raw_art_string): bool
    {
        $source_gd_image = imagecreatefromstring($raw_art_string);

        if (!is_resource($source_gd_image)) {
            return false;
        }

        // Crop the raw art to a 1200x1200 artboard.
        $dest_max_width = 1200;
        $dest_max_height = 1200;

        $source_image_width = imagesx($source_gd_image);
        $source_image_height = imagesy($source_gd_image);

        $source_aspect_ratio = $source_image_width / $source_image_height;
        $thumbnail_aspect_ratio = $dest_max_width / $dest_max_height;

        if ($source_image_width <= $dest_max_width && $source_image_height <= $dest_max_height) {
            $thumbnail_image_width = $source_image_width;
            $thumbnail_image_height = $source_image_height;
        } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
            $thumbnail_image_width = (int) ($dest_max_height * $source_aspect_ratio);
            $thumbnail_image_height = $dest_max_height;
        } else {
            $thumbnail_image_width = $dest_max_width;
            $thumbnail_image_height = (int) ($dest_max_width / $source_aspect_ratio);
        }

        $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
        imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);

        ob_start();
        imagejpeg($thumbnail_gd_image, NULL, 90);
        $album_art = ob_get_contents();
        ob_end_clean();

        imagedestroy($source_gd_image);
        imagedestroy($thumbnail_gd_image);

        $album_art_path = $media->getArtPath();
        $fs = $this->filesystem->getForStation($media->getStation());

        return $fs->put($album_art_path, $album_art);
    }

    /**
     * Read the contents of the album art from storage (if it exists).
     *
     * @param Entity\StationMedia $media
     * @return string|null
     */
    public function readAlbumArt(Entity\StationMedia $media): ?string
    {
        $album_art_path = $media->getArtPath();
        $fs = $this->filesystem->getForStation($media->getStation());

        if (!$fs->has($album_art_path)) {
            return null;
        }

        return $fs->read($album_art_path);
    }

    /**
     * Return the full path associated with a media entity.
     *
     * @param Entity\StationMedia $media
     * @return string
     */
    public function getFullPath(Entity\StationMedia $media): string
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        $uri = $media->getPathUri();

        return $fs->getFullPath($uri);
    }

    /**
     * Retrieve a key-value representation of all custom metadata for the specified media.
     *
     * @param Entity\StationMedia $media
     * @return array
     */
    public function getCustomFields(Entity\StationMedia $media)
    {
        $metadata_raw = $this->_em->createQuery('SELECT e FROM '.Entity\StationMediaCustomField::class.' e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->getArrayResult();

        $result = [];
        foreach($metadata_raw as $row) {
            $result[$row['field_id']] = $row['value'];
        }

        return $result;
    }

    /**
     * Set the custom metadata for a specified station based on a provided key-value array.
     *
     * @param Entity\StationMedia $media
     * @param array $custom_fields
     */
    public function setCustomFields(Entity\StationMedia $media, array $custom_fields)
    {
        $this->_em->createQuery('DELETE FROM '.Entity\StationMediaCustomField::class.' e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();

        foreach ($custom_fields as $field_id => $field_value) {
            /** @var Entity\CustomField $field */
            $field = $this->_em->getReference(Entity\CustomField::class, $field_id);

            $record = new Entity\StationMediaCustomField($media, $field);
            $record->setValue($field_value);
            $this->_em->persist($record);
        }

        $this->_em->flush();
    }
}
