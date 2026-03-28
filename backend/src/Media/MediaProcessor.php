<?php

declare(strict_types=1);

namespace App\Media;

use App\Cache\MediaListCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Repository\UnprocessableMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StorageLocation;
use App\Exception\CannotProcessMediaException;
use App\Message\AddNewMediaMessage;
use App\Message\ProcessCoverArtMessage;
use App\Message\ReprocessMediaMessage;
use Symfony\Component\Filesystem\Filesystem;

final class MediaProcessor
{
    use EntityManagerAwareTrait;
    use LoggerAwareTrait;

    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly UnprocessableMediaRepository $unprocessableMediaRepo,
        private readonly StorageLocationRepository $storageLocationRepo,
        private readonly MediaListCache $mediaListCache
    ) {
    }

    public function __invoke(
        ReprocessMediaMessage|AddNewMediaMessage|ProcessCoverArtMessage $message
    ): void {
        $storageLocation = $this->em->find(StorageLocation::class, $message->storage_location_id);
        if (!($storageLocation instanceof StorageLocation)) {
            return;
        }

        try {
            if ($message instanceof ReprocessMediaMessage) {
                $mediaRow = $this->em->find(StationMedia::class, $message->media_id);
                if ($mediaRow instanceof StationMedia) {
                    $this->process($storageLocation, $mediaRow, $message->force);
                }
            } else {
                $this->process($storageLocation, $message->path);
            }
        } catch (CannotProcessMediaException $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
        }
    }

    public function processAndUpload(
        StorageLocation $storageLocation,
        string $path,
        string $localPath
    ): ?StationMedia {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        if (!(new Filesystem())->exists($localPath)) {
            throw CannotProcessMediaException::forPath(
                $path,
                sprintf('Local file path "%s" not found.', $localPath)
            );
        }

        try {
            if (MimeType::isFileProcessable($localPath)) {
                $record = $this->mediaRepo->findByPath($path, $storageLocation);
                if (!($record instanceof StationMedia)) {
                    $record = new StationMedia($storageLocation, $path);
                }

                $this->mediaRepo->loadFromFile($record, $localPath, $fs);

                $record->mtime = time();
                $this->em->persist($record);
                $this->em->flush();

                $this->unprocessableMediaRepo->clearForPath($storageLocation, $path);

                return $record;
            }

            if (MimeType::isPathImage($localPath)) {
                $this->processCoverArt(
                    $storageLocation,
                    $path,
                    file_get_contents($localPath) ?: ''
                );
                return null;
            }

            throw CannotProcessMediaException::forPath(
                $path,
                'File type cannot be processed.'
            );
        } catch (CannotProcessMediaException $e) {
            $this->unprocessableMediaRepo->setForPath(
                $storageLocation,
                $path,
                $e->getMessage()
            );

            throw $e;
        } finally {
            $fs->uploadAndDeleteOriginal($localPath, $path);
            $this->mediaListCache->clearCache($storageLocation);
        }
    }

    public function process(
        StorageLocation $storageLocation,
        string|StationMedia $pathOrMedia,
        bool $force = false
    ): ?StationMedia {
        if ($pathOrMedia instanceof StationMedia) {
            $record = $pathOrMedia;
            $path = $pathOrMedia->path;
        } else {
            $record = null;
            $path = $pathOrMedia;
        }

        try {
            if (MimeType::isPathProcessable($path)) {
                $record ??= $this->mediaRepo->findByPath($path, $storageLocation);
                $created = false;
                if (!($record instanceof StationMedia)) {
                    $record = new StationMedia($storageLocation, $path);
                    $created = true;
                }

                $reprocessed = $this->processMedia($storageLocation, $record, $force || $created);

                if ($created || $reprocessed) {
                    $this->em->flush();
                    $this->unprocessableMediaRepo->clearForPath($storageLocation, $path);
                }

                return $record;
            }

            if (null === $record && MimeType::isPathImage($path)) {
                $this->processCoverArt(
                    $storageLocation,
                    $path
                );
                return null;
            }

            throw CannotProcessMediaException::forPath(
                $path,
                'File type cannot be processed.'
            );
        } catch (CannotProcessMediaException $e) {
            $this->unprocessableMediaRepo->setForPath(
                $storageLocation,
                $path,
                $e->getMessage()
            );

            throw $e;
        } finally {
            $this->mediaListCache->clearCache($storageLocation);
        }
    }

    public function processMedia(
        StorageLocation $storageLocation,
        StationMedia $media,
        bool $force = false
    ): bool {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();
        $path = $media->path;

        if (!$fs->fileExists($path)) {
            throw CannotProcessMediaException::forPath(
                $path,
                sprintf('Media path "%s" not found.', $path)
            );
        }

        $fileModified = $fs->lastModified($path);
        $mediaProcessedAt = $media->mtime;

        // No need to update if all of these conditions are true.
        if (!$force && $fileModified <= $mediaProcessedAt) {
            return false;
        }

        $fs->withLocalFile(
            $path,
            function ($localPath) use ($media, $fs): void {
                $this->mediaRepo->loadFromFile($media, $localPath, $fs);
            }
        );

        $media->mtime = time() + 5;
        $this->em->persist($media);

        $this->mediaListCache->clearCache($storageLocation);

        return true;
    }

    public function processCoverArt(
        StorageLocation $storageLocation,
        string $path,
        ?string $contents = null
    ): void {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        if (null === $contents) {
            if (!$fs->fileExists($path)) {
                throw CannotProcessMediaException::forPath(
                    $path,
                    sprintf('Cover art path "%s" not found.', $path)
                );
            }

            $contents = $fs->read($path);
        }

        $folderHash = StationMedia::getFolderHashForPath($path);
        $destPath = StationMedia::getFolderArtPath($folderHash);

        $fs->write(
            $destPath,
            AlbumArt::resize($contents)
        );

        $this->mediaListCache->clearCache($storageLocation);
    }
}
