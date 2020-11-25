<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Entity\StationPlaylist;
use App\Exception\CannotProcessMediaException;
use App\Exception\MediaProcessingException;
use App\Flysystem\Filesystem;
use App\Flysystem\FilesystemManager;
use App\Media\AlbumArt;
use App\Media\MetadataManagerInterface;
use App\Media\MimeType;
use App\Service\AudioWaveform;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class StationMediaRepository extends Repository
{
    protected CustomFieldRepository $customFieldRepo;

    protected StationPlaylistMediaRepository $spmRepo;

    protected StorageLocationRepository $storageLocationRepo;

    protected MetadataManagerInterface $metadataManager;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        LoggerInterface $logger,
        MetadataManagerInterface $metadataManager,
        CustomFieldRepository $customFieldRepo,
        StationPlaylistMediaRepository $spmRepo,
        StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct($em, $serializer, $settings, $logger);

        $this->customFieldRepo = $customFieldRepo;
        $this->spmRepo = $spmRepo;
        $this->storageLocationRepo = $storageLocationRepo;

        $this->metadataManager = $metadataManager;
    }

    /**
     * @param mixed $id
     * @param Entity\Station|Entity\StorageLocation $source
     */
    public function find($id, $source): ?Entity\StationMedia
    {
        if (Entity\StationMedia::UNIQUE_ID_LENGTH === strlen($id)) {
            $media = $this->findByUniqueId($id, $source);
            if ($media instanceof Entity\StationMedia) {
                return $media;
            }
        }

        $storageLocation = $this->getStorageLocation($source);
        return $this->repository->findOneBy([
            'storage_location' => $storageLocation,
            'id' => $id,
        ]);
    }

    /**
     * @param string $path
     * @param Entity\Station|Entity\StorageLocation $source
     */
    public function findByPath(string $path, $source): ?Entity\StationMedia
    {
        $storageLocation = $this->getStorageLocation($source);
        return $this->repository->findOneBy([
            'storage_location' => $storageLocation,
            'path' => $path,
        ]);
    }

    /**
     * @param string $uniqueId
     * @param Entity\Station|Entity\StorageLocation $source
     */
    public function findByUniqueId(string $uniqueId, $source): ?Entity\StationMedia
    {
        $storageLocation = $this->getStorageLocation($source);

        return $this->repository->findOneBy([
            'storage_location' => $storageLocation,
            'unique_id' => $uniqueId,
        ]);
    }

    /**
     * @param Entity\Station|Entity\StorageLocation $source
     *
     */
    protected function getStorageLocation($source): Entity\StorageLocation
    {
        if ($source instanceof Entity\StorageLocation) {
            return $source;
        }
        if ($source instanceof Entity\Station) {
            return $source->getMediaStorageLocation();
        }

        throw new InvalidArgumentException('Parameter must be a station or storage location.');
    }

    /**
     * @param Entity\Station|Entity\StorageLocation $source
     * @param string $path
     * @param string|null $uploadedFrom The original uploaded path (if this is a new upload).
     *
     * @throws Exception
     */
    public function getOrCreate(
        $source,
        string $path,
        ?string $uploadedFrom = null
    ): Entity\StationMedia {
        $path = FilesystemManager::stripPrefix($path);

        $record = $this->findByPath($path, $source);

        $created = false;
        if (!($record instanceof Entity\StationMedia)) {
            $storageLocation = $this->getStorageLocation($source);

            $record = new Entity\StationMedia($storageLocation, $path);
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
        $fs = $media->getStorageLocation()->getFilesystem();
        $mediaUri = $media->getPath();

        if (null !== $uploadedPath) {
            try {
                $this->loadFromFile($media, $uploadedPath);
                $this->writeWaveform($media, $uploadedPath);
            } finally {
                $fs->putFromLocal($uploadedPath, $mediaUri);
            }

            $mediaMtime = time();
        } else {
            if (!$fs->has($mediaUri)) {
                throw new MediaProcessingException(sprintf('Media path "%s" not found.', $mediaUri));
            }

            $mediaMtime = (int)$fs->getTimestamp($mediaUri);

            // No need to update if all of these conditions are true.
            if (!$force && !$media->needsReprocessing($mediaMtime)) {
                return false;
            }

            $fs->withLocalFile($mediaUri, function ($path) use ($media): void {
                $this->loadFromFile($media, $path);
                $this->writeWaveform($media, $path);
            });
        }

        $media->setMtime($mediaMtime);
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
        if (!MimeType::isFileProcessable($filePath)) {
            throw CannotProcessMediaException::forPath($filePath);
        }

        // Load metadata from supported files.
        $metadata = $this->metadataManager->getMetadata($filePath);

        $media->fromMetadata($metadata);

        // Persist the media record for later custom field operations.
        $this->em->persist($media);

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

    public function readAlbumArt(Entity\StationMedia $media): ?string
    {
        $fs = $media->getStorageLocation()->getFilesystem();
        $albumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());

        if (!$fs->has($albumArtPath)) {
            return null;
        }

        return $fs->read($albumArtPath);
    }

    public function writeAlbumArt(Entity\StationMedia $media, string $rawArtString): bool
    {
        $albumArt = AlbumArt::resize($rawArtString);

        $fs = $media->getStorageLocation()->getFilesystem();
        $albumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());

        $media->setArtUpdatedAt(time());
        $this->em->persist($media);

        return $fs->put($albumArtPath, $albumArt);
    }

    public function removeAlbumArt(Entity\StationMedia $media): void
    {
        $fs = $media->getStorageLocation()->getFilesystem();
        $currentAlbumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());

        $fs->delete($currentAlbumArtPath);

        $media->setArtUpdatedAt(0);
        $this->em->persist($media);
        $this->em->flush();
    }

    public function writeToFile(Entity\StationMedia $media): bool
    {
        $fs = $media->getStorageLocation()->getFilesystem();

        $metadata = $media->toMetadata();

        $art_path = Entity\StationMedia::getArtPath($media->getUniqueId());
        if ($fs->has($art_path)) {
            $metadata->setArtwork($fs->read($art_path));
        }

        // Write tags to the Media file.
        return $fs->withLocalFile($media->getPath(), function ($path) use ($media, $metadata) {
            if ($this->metadataManager->writeMetadata($metadata, $path)) {
                $media->setMtime(time() + 5);
                return true;
            }
            return false;
        });
    }

    public function updateWaveform(Entity\StationMedia $media): void
    {
        $fs = $media->getStorageLocation()->getFilesystem();
        $fs->withLocalFile($media->getPathUri(), function ($path) use ($media): void {
            $this->writeWaveform($media, $path);
        });
    }

    public function writeWaveform(Entity\StationMedia $media, string $path): bool
    {
        $waveform = AudioWaveform::getWaveformFor($path);

        $waveformPath = Entity\StationMedia::getWaveformPath($media->getUniqueId());

        $fs = $media->getStorageLocation()->getFilesystem();
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
        $fs = $media->getStorageLocation()->getFilesystem();
        $uri = $media->getPathUri();

        return $fs->getFullPath($uri);
    }

    /**
     * @param Entity\StationMedia $media
     * @param Filesystem|null $fs
     *
     * @return StationPlaylist[] The IDs as keys and records as values for all affected playlists.
     */
    public function remove(
        Entity\StationMedia $media,
        ?Filesystem $fs = null
    ): array {
        $fs ??= $media->getStorageLocation()->getFilesystem();

        // Clear related media.
        foreach ($media->getRelatedFilePaths() as $relatedFilePath) {
            try {
                $fs->delete($relatedFilePath);
            } catch (FileNotFoundException $e) {
                // Skip
            }
        }

        $affectedPlaylists = $this->spmRepo->clearPlaylistsFromMedia($media);

        $this->em->remove($media);
        $this->em->flush();

        return $affectedPlaylists;
    }
}
