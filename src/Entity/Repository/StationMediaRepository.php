<?php

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Entity\StationPlaylist;
use App\Environment;
use App\Exception\CannotProcessMediaException;
use App\Flysystem\Filesystem;
use App\Flysystem\FilesystemManager;
use App\Media\MetadataManager;
use App\Service\AudioWaveform;
use Exception;
use Generator;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
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

    protected UnprocessableMediaRepository $unprocessableMediaRepo;

    protected MetadataManager $metadataManager;

    protected FilesystemManager $filesystem;

    protected ImageManager $imageManager;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        MetadataManager $metadataManager,
        CustomFieldRepository $customFieldRepo,
        StationPlaylistMediaRepository $spmRepo,
        StorageLocationRepository $storageLocationRepo,
        UnprocessableMediaRepository $unprocessableMediaRepo,
        FilesystemManager $filesystem,
        ImageManager $imageManager
    ) {
        parent::__construct($em, $serializer, $environment, $logger);

        $this->customFieldRepo = $customFieldRepo;
        $this->spmRepo = $spmRepo;
        $this->storageLocationRepo = $storageLocationRepo;
        $this->unprocessableMediaRepo = $unprocessableMediaRepo;

        $this->metadataManager = $metadataManager;
        $this->filesystem = $filesystem;
        $this->imageManager = $imageManager;
    }

    /**
     * @param mixed $id
     * @param Entity\Station|Entity\StorageLocation $source
     *
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
    public function findByPath(string $path, $source): ?Entity\StationMedia
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

    public function iteratePaths(array $paths, $source): Generator
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
    public function findByUniqueId(string $uniqueId, $source): ?Entity\StationMedia
    {
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
                $this->loadFromFile($media, $uploadedPath);
            } finally {
                $fs->putFromLocal($uploadedPath, $path);
            }

            $mediaMtime = time();
        } else {
            if (!$fs->has($path)) {
                throw new CannotProcessMediaException(sprintf('Media path "%s" not found.', $path));
            }

            $mediaMtime = (int)$fs->getTimestamp($path);

            // No need to update if all of these conditions are true.
            if (!$force && !$media->needsReprocessing($mediaMtime)) {
                return false;
            }

            $fs->withLocalFile(
                $path,
                function ($localPath) use ($media): void {
                    $this->loadFromFile($media, $localPath);
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
     */
    public function loadFromFile(Entity\StationMedia $media, string $filePath): void
    {
        // Load metadata from supported files.
        $metadata = $this->metadataManager->getMetadata($media, $filePath);

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
            try {
                $this->writeAlbumArt($media, $artwork);
            } catch (\Exception $exception) {
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

    public function writeAlbumArt(Entity\StationMedia $media, string $rawArtString): bool
    {
        $media->setArtUpdatedAt(time());
        $this->em->persist($media);

        $fs = $this->getFilesystem($media);

        $albumArt = $this->imageManager->make($rawArtString);
        $albumArt->fit(
            1200,
            1200,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $albumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());
        $albumArtStream = $albumArt->stream('jpg', 90);

        return $fs->putStream($albumArtPath, $albumArtStream->detach());
    }

    public function removeAlbumArt(Entity\StationMedia $media): void
    {
        $fs = $this->getFilesystem($media);

        $currentAlbumArtPath = Entity\StationMedia::getArtPath($media->getUniqueId());
        $fs->delete($currentAlbumArtPath);

        $media->setArtUpdatedAt(0);
        $this->em->persist($media);
        $this->em->flush();
    }

    public function writeToFile(Entity\StationMedia $media): bool
    {
        $fs = $this->getFilesystem($media);

        $metadata = $media->toMetadata();

        $art_path = Entity\StationMedia::getArtPath($media->getUniqueId());
        if ($fs->has($art_path)) {
            $metadata->setArtwork($fs->read($art_path));
        }

        // Write tags to the Media file.
        $media->setMtime(time() + 5);
        $media->updateSongId();

        return $fs->withLocalFile(
            $media->getPath(),
            function ($path) use ($metadata) {
                try {
                    $this->metadataManager->writeMetadata($metadata, $path);
                    return true;
                } catch (CannotProcessMediaException $e) {
                    $this->logger->error(
                        $e->getMessage(),
                        [
                            'exception' => $e,
                        ]
                    );
                    return false;
                }
            }
        );
    }

    public function updateWaveform(Entity\StationMedia $media): void
    {
        $fs = $this->getFilesystem($media);
        $fs->withLocalFile(
            $media->getPath(),
            function ($path) use ($media): void {
                $this->writeWaveform($media, $path);
            }
        );
    }

    public function writeWaveform(Entity\StationMedia $media, string $path): bool
    {
        $waveform = AudioWaveform::getWaveformFor($path);

        $waveformPath = Entity\StationMedia::getWaveformPath($media->getUniqueId());

        $fs = $this->getFilesystem($media);
        return $fs->put(
            $waveformPath,
            json_encode(
                $waveform,
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR
            )
        );
    }

    /**
     * Return the full path associated with a media entity.
     *
     * @param Entity\StationMedia $media
     */
    public function getFullPath(Entity\StationMedia $media): string
    {
        $fs = $this->getFilesystem($media);
        $uri = $media->getPathUri();

        return $fs->getFullPath($uri);
    }

    /**
     * @param Entity\StationMedia $media
     * @param bool $deleteFile Whether to remove the media file itself (disabled for batch operations).
     * @param Filesystem|null $fs
     *
     * @return StationPlaylist[] The IDs as keys and records as values for all affected playlists.
     */
    public function remove(
        Entity\StationMedia $media,
        bool $deleteFile = false,
        ?Filesystem $fs = null
    ): array {
        $fs ??= $this->getFilesystem($media);

        // Clear related media.
        foreach ($media->getRelatedFilePaths() as $relatedFilePath) {
            try {
                $fs->delete($relatedFilePath);
            } catch (FileNotFoundException $e) {
                // Skip
            }
        }

        if ($deleteFile) {
            try {
                $fs->delete($media->getPath());
            } catch (FileNotFoundException $e) {
                // Skip
            }
        }

        $affectedPlaylists = $this->spmRepo->clearPlaylistsFromMedia($media);

        $this->em->remove($media);
        $this->em->flush();

        return $affectedPlaylists;
    }

    protected function getFilesystem(Entity\StationMedia $media, bool $cached = true): Filesystem
    {
        return $this->filesystem->getFilesystemForAdapter(
            $media->getStorageLocation()->getStorageAdapter(),
            $cached
        );
    }
}
