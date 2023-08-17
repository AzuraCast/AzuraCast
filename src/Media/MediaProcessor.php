<?php

declare(strict_types=1);

namespace App\Media;

use App\Container\EntityManagerAwareTrait;
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

    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly UnprocessableMediaRepository $unprocessableMediaRepo,
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function __invoke(
        ReprocessMediaMessage|AddNewMediaMessage|ProcessCoverArtMessage $message
    ): void {
        $storageLocation = $this->em->find(StorageLocation::class, $message->storage_location_id);
        if (!($storageLocation instanceof StorageLocation)) {
            return;
        }

        if ($message instanceof ReprocessMediaMessage) {
            $mediaRow = $this->em->find(StationMedia::class, $message->media_id);
            if ($mediaRow instanceof StationMedia) {
                $this->process($storageLocation, $mediaRow, $message->force);
            }
        } else {
            $this->process($storageLocation, $message->path);
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

                try {
                    $this->mediaRepo->loadFromFile($record, $localPath, $fs);

                    $record->setMtime(time());
                    $this->em->persist($record);
                } catch (CannotProcessMediaException $e) {
                    $this->unprocessableMediaRepo->setForPath(
                        $storageLocation,
                        $path,
                        $e->getMessage()
                    );

                    throw $e;
                }

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
        } finally {
            $fs->uploadAndDeleteOriginal($localPath, $path);
        }
    }

    public function process(
        StorageLocation $storageLocation,
        string|StationMedia $pathOrMedia,
        bool $force = false
    ): ?StationMedia {
        if ($pathOrMedia instanceof StationMedia) {
            $record = $pathOrMedia;
            $path = $pathOrMedia->getPath();
        } else {
            $record = null;
            $path = $pathOrMedia;
        }

        if (MimeType::isPathProcessable($path)) {
            $record ??= $this->mediaRepo->findByPath($path, $storageLocation);
            $created = false;
            if (!($record instanceof StationMedia)) {
                $record = new StationMedia($storageLocation, $path);
                $created = true;
            }

            try {
                $reprocessed = $this->processMedia($storageLocation, $record, $force);
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
    }

    public function processMedia(
        StorageLocation $storageLocation,
        StationMedia $media,
        bool $force = false
    ): bool {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();
        $path = $media->getPath();

        if (!$fs->fileExists($path)) {
            throw CannotProcessMediaException::forPath(
                $path,
                sprintf('Media path "%s" not found.', $path)
            );
        }

        $mediaMtime = $fs->lastModified($path);

        // No need to update if all of these conditions are true.
        if (!$force && !$media->needsReprocessing($mediaMtime)) {
            return false;
        }

        $fs->withLocalFile(
            $path,
            function ($localPath) use ($media, $fs): void {
                $this->mediaRepo->loadFromFile($media, $localPath, $fs);
            }
        );

        $media->setMtime($mediaMtime);
        $this->em->persist($media);

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
    }
}
