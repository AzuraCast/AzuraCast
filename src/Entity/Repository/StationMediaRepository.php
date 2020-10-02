<?php
namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Exception\MediaProcessingException;
use App\Flysystem\Filesystem;
use App\Media\AlbumArt;
use App\Media\Id3;
use App\Service\AudioWaveform;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use getid3_exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use voku\helper\UTF8;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class StationMediaRepository extends Repository
{
    protected Filesystem $filesystem;

    protected SongRepository $songRepo;

    protected CustomFieldRepository $customFieldRepo;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        LoggerInterface $logger,
        Filesystem $filesystem,
        SongRepository $songRepo,
        CustomFieldRepository $customFieldRepo
    ) {
        $this->filesystem = $filesystem;
        $this->songRepo = $songRepo;
        $this->customFieldRepo = $customFieldRepo;

        parent::__construct($em, $serializer, $settings, $logger);
    }

    /**
     * @param mixed $id
     * @param Entity\Station $station
     *
     * @return Entity\StationMedia|null
     */
    public function find($id, Entity\Station $station): ?Entity\StationMedia
    {
        if (Entity\StationMedia::UNIQUE_ID_LENGTH === strlen($id)) {
            $media = $this->findByUniqueId($id, $station);
            if ($media instanceof Entity\StationMedia) {
                return $media;
            }
        }

        return $this->repository->findOneBy([
            'station' => $station,
            'id' => $id,
        ]);
    }

    /**
     * @param string $path
     * @param Entity\Station $station
     *
     * @return Entity\StationMedia|null
     */
    public function findByPath(string $path, Entity\Station $station): ?Entity\StationMedia
    {
        return $this->repository->findOneBy([
            'station' => $station,
            'path' => $path,
        ]);
    }

    /**
     * @param string $uniqueId
     * @param Entity\Station $station
     *
     * @return Entity\StationMedia|null
     */
    public function findByUniqueId(string $uniqueId, Entity\Station $station): ?Entity\StationMedia
    {
        return $this->repository->findOneBy([
            'station' => $station,
            'unique_id' => $uniqueId,
        ]);
    }

    /**
     * @param Entity\Station $station
     * @param string $tmp_path
     * @param string $dest
     *
     * @return Entity\StationMedia
     */
    public function uploadFile(Entity\Station $station, $tmp_path, $dest): Entity\StationMedia
    {
        [, $dest_path] = explode('://', $dest, 2);

        $record = $this->repository->findOneBy([
            'station_id' => $station->getId(),
            'path' => $dest_path,
        ]);

        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia($station, $dest_path);
        }

        $this->loadFromFile($record, $tmp_path);

        $fs = $this->filesystem->getForStation($station);
        $fs->upload($tmp_path, $dest);

        $record->setMtime(time() + 5);

        $this->em->persist($record);
        $this->em->flush();

        return $record;
    }

    /**
     * Process metadata information from media file.
     *
     * @param Entity\StationMedia $media
     * @param string $file_path
     */
    public function loadFromFile(Entity\StationMedia $media, string $file_path): void
    {
        // Persist the media record for later custom field operations.
        $this->em->persist($media);

        // Load metadata from supported files.
        $file_info = Id3::read($file_path);

        // Set playtime length if the analysis was able to determine it
        if (is_numeric($file_info['playtime_seconds'])) {
            $media->setLength($file_info['playtime_seconds']);
        }

        $tagsToSet = [
            'title' => 'setTitle',
            'artist' => 'setArtist',
            'album' => 'setAlbum',
            'unsynchronised_lyric' => 'setLyrics',
            'isrc' => 'setIsrc',
        ];

        // Clear existing auto-assigned custom fields.
        $fieldCollection = $media->getCustomFields();

        foreach ($fieldCollection as $existingCustomField) {
            /** @var Entity\StationMediaCustomField $existingCustomField */
            if ($existingCustomField->getField()->hasAutoAssign()) {
                $this->em->remove($existingCustomField);
                $fieldCollection->removeElement($existingCustomField);
            }
        }

        $customFieldsToSet = $this->customFieldRepo->getAutoAssignableFields();

        if (!empty($file_info['tags'])) {
            foreach ($file_info['tags'] as $tag_type => $tag_data) {
                foreach ($tagsToSet as $tag => $tagMethod) {
                    if (!empty($tag_data[$tag][0])) {
                        $tagValue = $this->cleanUpString($tag_data[$tag][0]);
                        $media->{$tagMethod}($tagValue);
                    }
                }

                foreach ($customFieldsToSet as $tag => $customFieldKey) {
                    if (!empty($tag_data[$tag][0])) {
                        $tagValue = $this->cleanUpString($tag_data[$tag][0]);

                        $customFieldRow = new Entity\StationMediaCustomField($media, $customFieldKey);
                        $customFieldRow->setValue($tagValue);
                        $this->em->persist($customFieldRow);

                        $fieldCollection->add($customFieldRow);
                    }
                }
            }
        }

        if (!empty($file_info['attached_picture'][0])) {
            $picture = $file_info['attached_picture'][0];
            $this->writeAlbumArt($media, $picture['data']);
        } elseif (!empty($file_info['comments']['picture'][0])) {
            $picture = $file_info['comments']['picture'][0];
            $this->writeAlbumArt($media, $picture['data']);
        }

        // Attempt to derive title and artist from filename.
        if (empty($media->getTitle())) {
            $filename = pathinfo($media->getPath(), PATHINFO_FILENAME);
            $filename = str_replace('_', ' ', $filename);

            $string_parts = explode('-', $filename);

            // If not normally delimited, return "text" only.
            if (1 === count($string_parts)) {
                $media->setTitle(trim($filename));
                $media->setArtist('');
            } else {
                $media->setTitle(trim(array_pop($string_parts)));
                $media->setArtist(trim(implode('-', $string_parts)));
            }
        }

        $media->setSong($this->songRepo->getOrCreate([
            'artist' => $media->getArtist(),
            'title' => $media->getTitle(),
        ]));
    }

    protected function cleanUpString(string $original): string
    {
        $string = UTF8::encode('UTF-8', $original);
        $string = UTF8::fix_simple_utf8($string);
        return UTF8::clean(
            $string,
            true,
            true,
            true,
            true,
            true
        );
    }

    /**
     * Crop album art and write the resulting image to storage.
     *
     * @param Entity\StationMedia $media
     * @param string $rawArtString The raw image data, as would be retrieved from file_get_contents.
     *
     * @return bool
     */
    public function writeAlbumArt(Entity\StationMedia $media, $rawArtString): bool
    {
        $albumArt = AlbumArt::resize($rawArtString);

        $fs = $this->filesystem->getForStation($media->getStation());
        $albumArtPath = $media->getArtPath();

        $media->setArtUpdatedAt(time());
        $this->em->persist($media);
        $this->em->flush();

        return $fs->put($albumArtPath, $albumArt);
    }

    public function removeAlbumArt(Entity\StationMedia $media): void
    {
        // Remove the album art, if it exists.
        $fs = $this->filesystem->getForStation($media->getStation());
        $currentAlbumArtPath = $media->getArtPath();

        $fs->delete($currentAlbumArtPath);

        $media->setArtUpdatedAt(0);
        $this->em->persist($media);
        $this->em->flush();
    }

    /**
     * @param Entity\Station $station
     * @param string $path
     *
     * @return Entity\StationMedia
     * @throws Exception
     */
    public function getOrCreate(Entity\Station $station, $path): Entity\StationMedia
    {
        if (strpos($path, '://') !== false) {
            [, $path] = explode('://', $path, 2);
        }

        $record = $this->repository->findOneBy([
            'station_id' => $station->getId(),
            'path' => $path,
        ]);

        $created = false;
        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia($station, $path);
            $created = true;
        }

        $this->processMedia($record);

        if ($created) {
            $this->em->persist($record);
            $this->em->flush();
        }

        return $record;
    }

    /**
     * Run media through the "processing" steps: loading from file and setting up any missing metadata.
     *
     * @param Entity\StationMedia $media
     * @param bool $force
     *
     * @return bool Whether reprocessing was required for this file.
     */
    public function processMedia(Entity\StationMedia $media, $force = false): bool
    {
        $media_uri = $media->getPathUri();

        $fs = $this->filesystem->getForStation($media->getStation());
        if (!$fs->has($media_uri)) {
            throw new MediaProcessingException(sprintf('Media path "%s" not found.', $media_uri));
        }

        $media_mtime = (int)$fs->getTimestamp($media_uri);

        // No need to update if all of these conditions are true.
        if (!$force && !$media->needsReprocessing($media_mtime)) {
            return false;
        }

        $tmp_uri = null;

        try {
            $tmp_path = $fs->getFullPath($media_uri);
        } catch (InvalidArgumentException $e) {
            $tmp_uri = $fs->copyToTemp($media_uri);
            $tmp_path = $fs->getFullPath($tmp_uri);
        }

        $this->loadFromFile($media, $tmp_path);
        $this->writeWaveform($media, $tmp_path);

        if (null !== $tmp_uri) {
            $fs->delete($tmp_uri);
        }

        $media->setMtime($media_mtime);
        $this->em->persist($media);

        return true;
    }

    /**
     * Write modified metadata directly to the file as ID3 information.
     *
     * @param Entity\StationMedia $media
     *
     * @return bool
     * @throws getid3_exception
     */
    public function writeToFile(Entity\StationMedia $media): bool
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        $media_uri = $media->getPathUri();
        $tmp_uri = null;

        try {
            $tmp_path = $fs->getFullPath($media_uri);
        } catch (InvalidArgumentException $e) {
            $tmp_uri = $fs->copyToTemp($media_uri);
            $tmp_path = $fs->getFullPath($tmp_uri);
        }

        $tag_data = [
            'title' => [$media->getTitle()],
            'artist' => [$media->getArtist()],
            'album' => [$media->getAlbum()],
            'unsynchronised_lyric' => [$media->getLyrics()],
        ];

        $art_path = $media->getArtPath();
        if ($fs->has($art_path)) {
            $tag_data['attached_picture'][0] = [
                'encodingid' => 0, // ISO-8859-1; 3=UTF8 but only allowed in ID3v2.4
                'description' => 'cover art',
                'data' => $fs->read($art_path),
                'picturetypeid' => 0x03,
                'mime' => 'image/jpeg',
            ];

            $tag_data['comments']['picture'][0] = $tag_data['attached_picture'][0];
        }

        // write tags
        if (Id3::write($tmp_path, $tag_data)) {
            $media->setMtime(time() + 5);

            if (null !== $tmp_uri) {
                $fs->updateFromTemp($tmp_uri, $media_uri);
            }
            return true;
        }

        return false;
    }

    public function updateWaveform(Entity\StationMedia $media): void
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        $mediaUri = $media->getPathUri();
        $tmpUri = null;
        try {
            $tmpPath = $fs->getFullPath($mediaUri);
        } catch (InvalidArgumentException $e) {
            $tmpUri = $fs->copyToTemp($mediaUri);
            $tmpPath = $fs->getFullPath($tmpUri);
        }

        $this->writeWaveform($media, $tmpPath);

        if (null !== $tmpUri) {
            $fs->delete($tmpUri);
        }
    }

    public function writeWaveform(Entity\StationMedia $media, string $path): bool
    {
        $waveform = AudioWaveform::getWaveformFor($path);

        $waveformPath = $media->getWaveformPath();

        $fs = $this->filesystem->getForStation($media->getStation());
        return $fs->put(
            $waveformPath,
            json_encode($waveform, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Read the contents of the album art from storage (if it exists).
     *
     * @param Entity\StationMedia $media
     *
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
     *
     * @return string
     */
    public function getFullPath(Entity\StationMedia $media): string
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        $uri = $media->getPathUri();

        return $fs->getFullPath($uri);
    }
}
