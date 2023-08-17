<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Container\LoggerAwareTrait;
use App\Doctrine\Repository;
use App\Entity\Song;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaCustomField;
use App\Entity\StorageLocation;
use App\Exception\NotFoundException;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Media\AlbumArt;
use App\Media\MetadataManager;
use App\Media\RemoteAlbumArt;
use App\Service\AudioWaveform;
use Exception;
use Generator;
use League\Flysystem\FilesystemException;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * @extends Repository<StationMedia>
 */
final class StationMediaRepository extends Repository
{
    use LoggerAwareTrait;

    protected string $entityClass = StationMedia::class;

    public function __construct(
        private readonly MetadataManager $metadataManager,
        private readonly RemoteAlbumArt $remoteAlbumArt,
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public function findForStation(int|string $id, Station $station): ?StationMedia
    {
        if (!is_numeric($id) && StationMedia::UNIQUE_ID_LENGTH === strlen($id)) {
            $media = $this->findByUniqueId($id, $station);
            if ($media instanceof StationMedia) {
                return $media;
            }
        }

        $storageLocation = $this->getStorageLocation($station);

        /** @var StationMedia|null $media */
        $media = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'id' => $id,
            ]
        );

        return $media;
    }

    public function requireForStation(int|string $id, Station $station): StationMedia
    {
        $record = $this->findForStation($id, $station);
        if (null === $record) {
            throw new NotFoundException();
        }
        return $record;
    }

    /**
     * @param string $path
     * @param Station|StorageLocation $source
     *
     */
    public function findByPath(
        string $path,
        Station|StorageLocation $source
    ): ?StationMedia {
        $storageLocation = $this->getStorageLocation($source);

        /** @var StationMedia|null $media */
        $media = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'path' => $path,
            ]
        );

        return $media;
    }

    public function iteratePaths(array $paths, Station|StorageLocation $source): Generator
    {
        $storageLocation = $this->getStorageLocation($source);

        foreach ($paths as $path) {
            $media = $this->findByPath($path, $storageLocation);
            if ($media instanceof StationMedia) {
                yield $path => $media;
            }
        }
    }

    /**
     * @param string $uniqueId
     * @param Station|StorageLocation $source
     *
     */
    public function findByUniqueId(
        string $uniqueId,
        Station|StorageLocation $source
    ): ?StationMedia {
        $storageLocation = $this->getStorageLocation($source);

        /** @var StationMedia|null $media */
        $media = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'unique_id' => $uniqueId,
            ]
        );

        return $media;
    }

    public function requireByUniqueId(
        string $uniqueId,
        Station|StorageLocation $source
    ): StationMedia {
        $record = $this->findByUniqueId($uniqueId, $source);
        if (null === $record) {
            throw new NotFoundException();
        }
        return $record;
    }

    private function getStorageLocation(Station|StorageLocation $source): StorageLocation
    {
        if ($source instanceof Station) {
            return $source->getMediaStorageLocation();
        }

        return $source;
    }

    /**
     * Process metadata information from media file.
     *
     * @param StationMedia $media
     * @param string $filePath
     * @param ExtendedFilesystemInterface|null $fs
     */
    public function loadFromFile(
        StationMedia $media,
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
            /** @var StationMediaCustomField $existingCustomField */
            if ($existingCustomField->getField()->hasAutoAssign()) {
                $this->em->remove($existingCustomField);
                $fieldCollection->removeElement($existingCustomField);
            }
        }

        $customFieldsToSet = $this->customFieldRepo->getAutoAssignableFields();
        $tags = $metadata->getTags();
        foreach ($customFieldsToSet as $tag => $customFieldKey) {
            if (!empty($tags[$tag])) {
                $customFieldRow = new StationMediaCustomField($media, $customFieldKey);
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

            $songObj = Song::createFromText($filename);
            $media->setSong($songObj);
        }

        // Force a text property to auto-generate from artist/title
        $media->setText($media->getText());

        // Generate a song_id hash based on the track
        $media->updateSongId();
    }

    public function updateAlbumArt(
        StationMedia $media,
        string $rawArtString
    ): bool {
        $fs = $this->getFilesystem($media);

        $this->writeAlbumArt($media, $rawArtString, $fs);
        return $this->writeToFile($media, $fs);
    }

    public function writeAlbumArt(
        StationMedia $media,
        string $rawArtString,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);

        $media->setArtUpdatedAt(time());
        $this->em->persist($media);

        $albumArtPath = StationMedia::getArtPath($media->getUniqueId());
        $albumArtString = AlbumArt::resize($rawArtString);

        $fs->write($albumArtPath, $albumArtString);
    }

    public function removeAlbumArt(
        StationMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);

        $currentAlbumArtPath = StationMedia::getArtPath($media->getUniqueId());
        $fs->delete($currentAlbumArtPath);

        $media->setArtUpdatedAt(0);
        $this->em->persist($media);
        $this->em->flush();

        $this->writeToFile($media, $fs);
    }

    public function writeToFile(
        StationMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): bool {
        $fs ??= $this->getFilesystem($media);

        $metadata = $media->toMetadata();

        $artPath = StationMedia::getArtPath($media->getUniqueId());
        if ($fs->fileExists($artPath)) {
            $metadata->setArtwork($fs->read($artPath));
        }

        // Write tags to the Media file.
        $media->setMtime(time() + 5);
        $media->updateSongId();

        return $fs->withLocalFile(
            $media->getPath(),
            function ($path) use ($metadata) {
                $this->metadataManager->write($metadata, $path);
                return true;
            }
        );
    }

    public function updateWaveform(
        StationMedia $media,
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
        StationMedia $media,
        string $path,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->getFilesystem($media);

        $waveform = AudioWaveform::getWaveformFor($path);
        $waveformPath = StationMedia::getWaveformPath($media->getUniqueId());

        $fs->write(
            $waveformPath,
            json_encode(
                $waveform,
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR
            )
        );
    }

    /**
     * @param StationMedia $media
     * @param bool $deleteFile Whether to remove the media file itself (disabled for batch operations).
     * @param ExtendedFilesystemInterface|null $fs
     *
     * @return array<int, int> Affected playlist records (id => id)
     */
    public function remove(
        StationMedia $media,
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

    private function getFilesystem(StationMedia $media): ExtendedFilesystemInterface
    {
        return $this->storageLocationRepo->getAdapter($media->getStorageLocation())
            ->getFilesystem();
    }
}
