<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use App\Exception\CannotProcessMediaException;
use App\Media\MetadataManager;
use App\Media\RemoteAlbumArt;
use App\Service\AudioWaveform;
use Azura\Files\ExtendedFilesystemInterface;
use Exception;
use Generator;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class StationMediaRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        protected MetadataManager $metadataManager,
        protected RemoteAlbumArt $remoteAlbumArt,
        protected CustomFieldRepository $customFieldRepo,
        protected StationPlaylistMediaRepository $spmRepo,
        protected StorageLocationRepository $storageLocationRepo,
        protected UnprocessableMediaRepository $unprocessableMediaRepo,
        protected ImageManager $imageManager
    ) {
        parent::__construct($em, $serializer, $environment, $logger);
    }

    /**
     * @param mixed $id
     * @param Entity\Station|Entity\StorageLocation $source
     *
     */
    public function find(mixed $id, Entity\Station|Entity\StorageLocation $source): ?Entity\StationMedia
    {
        if (is_string($id) && Entity\StationMedia::UNIQUE_ID_LENGTH === strlen($id)) {
            $media = $this->findByUniqueId($id, $source);
            if ($media instanceof Entity\StationMedia) {
                return $media;
            }
        }

        $storageLocation = $this->getStorageLocation($source);

        /** @var Entity\StationMedia|null $media */
        $media = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'id' => $id,
            ]
        );

        return $media;
    }

    /**
     * @param string $path
     * @param Entity\Station|Entity\StorageLocation $source
     *
     */
    public function findByPath(string $path, Entity\Station|Entity\StorageLocation $source): ?Entity\StationMedia
    {
        $storageLocation = $this->getStorageLocation($source);

        /** @var Entity\StationMedia|null $media */
        $media = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'path' => $path,
            ]
        );

        return $media;
    }

    public function iteratePaths(array $paths, Entity\Station|Entity\StorageLocation $source): Generator
    {
        $storageLocation = $this->getStorageLocation($source);

        foreach ($paths as $path) {
            $media = $this->findByPath($path, $storageLocation);
            if ($media instanceof Entity\StationMedia) {
                yield $path => $media;
            }
        }
    }

    /**
     * @param string $uniqueId
     * @param Entity\Station|Entity\StorageLocation $source
     *
     */
    public function findByUniqueId(
        string $uniqueId,
        Entity\Station|Entity\StorageLocation $source
    ): ?Entity\StationMedia {
        $storageLocation = $this->getStorageLocation($source);

        /** @var Entity\StationMedia|null $media */
        $media = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'unique_id' => $uniqueId,
            ]
        );

        return $media;
    }

    protected function getStorageLocation(Entity\Station|Entity\StorageLocation $source): Entity\StorageLocation
    {
        if ($source instanceof Entity\Station) {
            return $source->getMediaStorageLocation();
        }

        return $source;
    }

    /**
     * @param Entity\Station|Entity\StorageLocation $source
     * @param string $path
     * @param string|null $uploadedFrom The original uploaded path (if this is a new upload).
     *
     * @throws Exception
     */
    public function getOrCreate(
        Entity\Station|Entity\StorageLocation $source,
        string $path,
        ?string $uploadedFrom = null
    ): Entity\StationMedia {
        $record = $this->findByPath($path, $source);
        $storageLocation = $this->getStorageLocation($source);

        $created = false;
        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia($storageLocation, $path);
            $created = true;
        }

        try {
            $reprocessed = $this->processMedia($record, $created, $uploadedFrom);
        } catch (CannotProcessMediaException $e) {
            $this->unprocessableMediaRepo->setForPath(
                $storageLocation,
                $path,
                $e->getMessage()
            );

            throw $e;
        }

        if ($created || $reprocessed) {
            $this->em->flush();

            $this->unprocessableMediaRepo->clearForPath($storageLocation, $path);
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
        $fs = $this->getFilesystem($media);
        $path = $media->getPath();

        if (null !== $uploadedPath) {
            try {
                $this->loadFromFile($media, $uploadedPath, $fs);
            } finally {
                $fs->uploadAndDeleteOriginal($uploadedPath, $path);
            }

            $mediaMtime = time();
        } else {
            if (!$fs->fileExists($path)) {
                throw new CannotProcessMediaException(sprintf('Media path "%s" not found.', $path));
            }

            $mediaMtime = $fs->lastModified($path);

            // No need to update if all of these conditions are true.
            if (!$force && !$media->needsReprocessing($mediaMtime)) {
                return false;
            }

            $fs->withLocalFile(
                $path,
                function ($localPath) use ($media, $fs): void {
                    $this->loadFromFile($media, $localPath, $fs);
                }
            );
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
     * @param ExtendedFilesystemInterface|null $fs
     */
    public function loadFromFile(
        Entity\StationMedia $media,
        string $filePath,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        // Load metadata from supported files.
        $metadata = $this->metadataManager->read($filePath);

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

        if (empty($artwork) && $this->remoteAlbumArt->enableForMedia()) {
            $artwork = $this->remoteAlbumArt->getArtwork($media);
        }

        if (!empty($artwork)) {
            try {
                $this->writeAlbumArt($media, $artwork, $fs);
            } catch (Exception $exception) {
                $this->logger->error(
                    sprintf(
                        'Album Artwork for "%s" could not be processed: "%s"',
                        $filePath,
                        $exception->getMessage()
                    ),
                    $exception->getTrace()
                );
            }
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

    public function updateAlbumArt(
        Entity\StationMedia $media,
        string $rawArtString
    ): bool {
        $fs = $this->getFilesystem($media);

        $this->writeAlbumArt($media, $rawArtString, $fs);
        return $this->writeToFile($media, $fs);
    }

    public function writeAlbumArt(
        Entity\StationMedia $media,
        string $rawArtString,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);

        $media->setArtUpdatedAt(time());
        $this->em->persist($media);

        $albumArt = $this->imageManager->make($rawArtString);
        $albumArt->fit(
            1200,
            1200,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $albumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());
        $albumArtStream = $albumArt->stream('jpg');

        $fs->writeStream($albumArtPath, $albumArtStream->detach());
    }

    public function removeAlbumArt(
        Entity\StationMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);

        $currentAlbumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());
        $fs->delete($currentAlbumArtPath);

        $media->setArtUpdatedAt(0);
        $this->em->persist($media);
        $this->em->flush();

        $this->writeToFile($media, $fs);
    }

    public function writeToFile(
        Entity\StationMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): bool {
        $fs ??= $this->getFilesystem($media);

        $metadata = $media->toMetadata();

        $art_path = Entity\StationMedia::getArtPath($media->getUniqueId());
        if ($fs->fileExists($art_path)) {
            $metadata->setArtwork($fs->read($art_path));
        }

        // Write tags to the Media file.
        $media->setMtime(time() + 5);
        $media->updateSongId();

        return $fs->withLocalFile(
            $media->getPath(),
            function ($path) use ($metadata) {
                try {
                    $this->metadataManager->write($metadata, $path);
                    return true;
                } catch (CannotProcessMediaException $e) {
                    throw $e;
                }
            }
        );
    }

    public function updateWaveform(
        Entity\StationMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);
        $fs->withLocalFile(
            $media->getPath(),
            function ($path) use ($media, $fs): void {
                $this->writeWaveform($media, $path, $fs);
            }
        );
    }

    public function writeWaveform(
        Entity\StationMedia $media,
        string $path,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);

        $waveform = AudioWaveform::getWaveformFor($path);
        $waveformPath = Entity\StationMedia::getWaveformPath($media->getUniqueId());

        $fs->write(
            $waveformPath,
            json_encode(
                $waveform,
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR
            )
        );
    }

    /**
     * @param Entity\StationMedia $media
     * @param bool $deleteFile Whether to remove the media file itself (disabled for batch operations).
     * @param ExtendedFilesystemInterface|null $fs
     *
     * @return Entity\StationPlaylist[] The IDs as keys and records as values for all affected playlists.
     */
    public function remove(
        Entity\StationMedia $media,
        bool $deleteFile = false,
        ?ExtendedFilesystemInterface $fs = null
    ): array {
        $fs ??= $this->getFilesystem($media);

        // Clear related media.
        foreach ($media->getRelatedFilePaths() as $relatedFilePath) {
            try {
                $fs->delete($relatedFilePath);
            } catch (FilesystemException) {
                // Skip
            }
        }

        if ($deleteFile) {
            try {
                $fs->delete($media->getPath());
            } catch (FilesystemException) {
                // Skip
            }
        }

        $affectedPlaylists = $this->spmRepo->clearPlaylistsFromMedia($media);

        $this->em->remove($media);
        $this->em->flush();

        return $affectedPlaylists;
    }

    protected function getFilesystem(Entity\StationMedia $media): ExtendedFilesystemInterface
    {
        return $media->getStorageLocation()->getFilesystem();
    }
}
