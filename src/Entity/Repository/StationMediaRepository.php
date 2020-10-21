<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Entity\StationPlaylist;
use App\Exception\MediaProcessingException;
use App\Flysystem\Filesystem;
use App\Media\AlbumArt;
use App\Media\MetadataManagerInterface;
use App\Service\AudioWaveform;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use getid3_exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class StationMediaRepository extends Repository
{
    protected Filesystem $filesystem;

    protected CustomFieldRepository $customFieldRepo;

    protected StationPlaylistMediaRepository $spmRepo;

    protected MetadataManagerInterface $metadataManager;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        LoggerInterface $logger,
        Filesystem $filesystem,
        MetadataManagerInterface $metadataManager,
        CustomFieldRepository $customFieldRepo,
        StationPlaylistMediaRepository $spmRepo
    ) {
        parent::__construct($em, $serializer, $settings, $logger);

        $this->filesystem = $filesystem;
        $this->metadataManager = $metadataManager;

        $this->customFieldRepo = $customFieldRepo;
        $this->spmRepo = $spmRepo;
    }

    /**
     * @param mixed $id
     * @param Entity\Station $station
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
     * @param string $path
     * @param string|null $uploadedFrom The original uploaded path (if this is a new upload).
     *
     * @throws Exception
     */
    public function getOrCreate(
        Entity\Station $station,
        string $path,
        ?string $uploadedFrom = null
    ): Entity\StationMedia {
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

        $reprocessed = $this->processMedia($record, $created, $uploadedFrom);

        if ($created || $reprocessed) {
            $this->em->flush();
        }

        return $record;
    }

    /**
     * Run media through the "processing" steps: loading from file and setting up any missing metadata.
     *
     * @param Entity\StationMedia $media
     * @param bool $force
     * @param string|null $uploadedPath The uploaded path (if this is a new upload).
     *
     * @return bool Whether reprocessing was required for this file.
     */
    public function processMedia(
        Entity\StationMedia $media,
        bool $force = false,
        ?string $uploadedPath = null
    ): bool {
        $fs = $this->filesystem->getForStation($media->getStation(), false);

        $tmp_uri = null;
        $media_uri = $media->getPathUri();

        if (null !== $uploadedPath) {
            $tmp_path = $uploadedPath;

            $media_mtime = time();
        } else {
            if (!$fs->has($media_uri)) {
                throw new MediaProcessingException(sprintf('Media path "%s" not found.', $media_uri));
            }

            $media_mtime = (int)$fs->getTimestamp($media_uri);

            // No need to update if all of these conditions are true.
            if (!$force && !$media->needsReprocessing($media_mtime)) {
                return false;
            }

            try {
                $tmp_path = $fs->getFullPath($media_uri);
            } catch (InvalidArgumentException $e) {
                $tmp_uri = $fs->copyToTemp($media_uri);
                $tmp_path = $fs->getFullPath($tmp_uri);
            }
        }

        $this->loadFromFile($media, $tmp_path);
        $this->writeWaveform($media, $tmp_path);

        if (null !== $uploadedPath) {
            $fs->upload($uploadedPath, $media_uri);
        } elseif (null !== $tmp_uri) {
            $fs->delete($tmp_uri);
        }

        $media->setMtime($media_mtime);
        $this->em->persist($media);

        return true;
    }

    /**
     * Process metadata information from media file.
     *
     * @param Entity\StationMedia $media
     * @param string $filePath
     */
    public function loadFromFile(Entity\StationMedia $media, string $filePath): void
    {
        // Persist the media record for later custom field operations.
        $this->em->persist($media);

        // Load metadata from supported files.
        $metadata = $this->metadataManager->getMetadata($filePath);

        $media->fromMetadata($metadata);

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
        $tags = $metadata->getTags();
        foreach ($customFieldsToSet as $tag => $customFieldKey) {
            if (!empty($tags[$tag])) {
                $customFieldRow = new Entity\StationMediaCustomField($media, $customFieldKey);
                $customFieldRow->setValue($tags[$tag]);
                $this->em->persist($customFieldRow);

                $fieldCollection->add($customFieldRow);
            }
        }

        $artwork = $metadata->getArtwork();
        if (!empty($artwork)) {
            $this->writeAlbumArt($media, $artwork);
        }

        // Attempt to derive title and artist from filename.
        $artist = $media->getArtist();
        $title = $media->getTitle();

        if (null === $artist || null === $title) {
            $filename = pathinfo($media->getPath(), PATHINFO_FILENAME);
            $filename = str_replace('_', ' ', $filename);

            $songObj = Entity\Song::createFromText($filename);
            $media->setSong($songObj);
        }

        // Force a text property to auto-generate from artist/title
        $media->setText($media->getText());

        // Generate a song_id hash based on the track
        $media->updateSongId();
    }

    /**
     * Read the contents of the album art from storage (if it exists).
     *
     * @param Entity\StationMedia $media
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
     * Crop album art and write the resulting image to storage.
     *
     * @param Entity\StationMedia $media
     * @param string $rawArtString The raw image data, as would be retrieved from file_get_contents.
     */
    public function writeAlbumArt(Entity\StationMedia $media, $rawArtString): bool
    {
        $albumArt = AlbumArt::resize($rawArtString);

        $fs = $this->filesystem->getForStation($media->getStation());
        $albumArtPath = $media->getArtPath();

        $media->setArtUpdatedAt(time());
        $this->em->persist($media);

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
     * Write modified metadata directly to the file as ID3 information.
     *
     * @param Entity\StationMedia $media
     *
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

        $metadata = $media->toMetadata();

        $art_path = $media->getArtPath();
        if ($fs->has($art_path)) {
            $metadata->setArtwork($fs->read($art_path));
        }

        // write tags
        if ($this->metadataManager->writeMetadata($metadata, $tmp_path)) {
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
     * Return the full path associated with a media entity.
     *
     * @param Entity\StationMedia $media
     */
    public function getFullPath(Entity\StationMedia $media): string
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        $uri = $media->getPathUri();

        return $fs->getFullPath($uri);
    }

    /**
     * @param Entity\StationMedia $media
     *
     * @return StationPlaylist[] The IDs as keys and records as values for all affected playlists.
     */
    public function remove(Entity\StationMedia $media): array
    {
        $fs = $this->filesystem->getForStation($media->getStation());

        // Clear related media.
        foreach ($media->getRelatedFilePaths() as $relatedFilePath) {
            if ($fs->has($relatedFilePath)) {
                $fs->delete($relatedFilePath);
            }
        }

        $affectedPlaylists = $this->spmRepo->clearPlaylistsFromMedia($media);

        $this->em->remove($media);
        $this->em->flush();

        return $affectedPlaylists;
    }
}
