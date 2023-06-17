<?php

declare(strict_types=1);

namespace App\Media;

use App\Container\EntityManagerAwareTrait;
use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\Interfaces\PathAwareInterface;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Repository\UnprocessableMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylistFolder;
use App\Entity\StorageLocation;
use App\Entity\UnprocessableMedia;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Utilities\File;
use Throwable;

final class BatchUtilities
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly UnprocessableMediaRepository $unprocessableMediaRepo,
        private readonly StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public function handleRename(
        string $from,
        string $to,
        StorageLocation $storageLocation,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        if ($fs->isDir($to)) {
            // Update the paths of all media contained within the directory.
            $toRename = [
                $this->iterateMediaInDirectory($storageLocation, $from),
                $this->iterateUnprocessableMediaInDirectory($storageLocation, $from),
                $this->iteratePlaylistFoldersInDirectory($storageLocation, $from),
            ];

            foreach ($toRename as $iterator) {
                foreach ($iterator as $record) {
                    /** @var PathAwareInterface $record */
                    $record->setPath(
                        File::renameDirectoryInPath($record->getPath(), $from, $to)
                    );
                    $this->em->persist($record);
                }
            }
        } else {
            $record = $this->mediaRepo->findByPath($from, $storageLocation);

            if ($record instanceof StationMedia) {
                $record->setPath($to);
                $this->em->persist($record);
                $this->em->flush();
            } else {
                $record = $this->unprocessableMediaRepo->findByPath($from, $storageLocation);

                if ($record instanceof UnprocessableMedia) {
                    $record->setPath($to);
                    $this->em->persist($record);
                    $this->em->flush();
                }
            }
        }
    }

    /**
     * @param array $files
     * @param array $directories
     * @param StorageLocation $storageLocation
     * @param ExtendedFilesystemInterface|null $fs
     *
     * @return array<int, int> Affected playlist IDs
     */
    public function handleDelete(
        array $files,
        array $directories,
        StorageLocation $storageLocation,
        ?ExtendedFilesystemInterface $fs = null
    ): array {
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();
        $affectedPlaylists = [];

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateMedia($storageLocation, $files) as $media) {
            try {
                $affectedPlaylists += $this->mediaRepo->remove($media, false, $fs);
            } catch (Throwable) {
            }
        }

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateUnprocessableMedia($storageLocation, $files) as $unprocessableMedia) {
            $this->em->remove($unprocessableMedia);
        }

        foreach ($directories as $dir) {
            foreach ($this->iteratePlaylistFoldersInDirectory($storageLocation, $dir) as $playlistFolder) {
                $this->em->remove($playlistFolder);
            }
        }

        $this->em->flush();

        return $affectedPlaylists;
    }

    /**
     * Iterate through the found media records, while occasionally flushing and clearing the entity manager.
     *
     * @note This function flushes the entity manager.
     *
     * @param StorageLocation $storageLocation
     * @param array $paths
     *
     * @return iterable|StationMedia[]
     */
    public function iterateMedia(StorageLocation $storageLocation, array $paths): iterable
    {
        return ReadWriteBatchIteratorAggregate::fromTraversableResult(
            $this->mediaRepo->iteratePaths($paths, $storageLocation),
            $this->em,
            25
        );
    }

    /**
     * @param StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|StationMedia[]
     */
    public function iterateMediaInDirectory(StorageLocation $storageLocation, string $dir): iterable
    {
        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT sm
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
                AND sm.path LIKE :path
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->setParameter('path', $dir . '/%');

        return ReadWriteBatchIteratorAggregate::fromQuery($query, 25);
    }

    /**
     * Iterate through unprocessable media, while occasionally flushing and clearing the entity manager.
     *
     * @note This function flushes the entity manager.
     *
     * @param StorageLocation $storageLocation
     * @param array $paths
     *
     * @return iterable|UnprocessableMedia[]
     */
    public function iterateUnprocessableMedia(StorageLocation $storageLocation, array $paths): iterable
    {
        return ReadWriteBatchIteratorAggregate::fromTraversableResult(
            $this->unprocessableMediaRepo->iteratePaths($paths, $storageLocation),
            $this->em,
            25
        );
    }

    /**
     * @param StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|UnprocessableMedia[]
     */
    public function iterateUnprocessableMediaInDirectory(
        StorageLocation $storageLocation,
        string $dir
    ): iterable {
        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT upm
                FROM App\Entity\UnprocessableMedia upm
                WHERE upm.storage_location = :storageLocation
                AND upm.path LIKE :path
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->setParameter('path', $dir . '/%');

        return ReadWriteBatchIteratorAggregate::fromQuery($query, 25);
    }

    /**
     * @param StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|StationPlaylistFolder[]
     */
    public function iteratePlaylistFoldersInDirectory(
        StorageLocation $storageLocation,
        string $dir
    ): iterable {
        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT spf
                FROM App\Entity\StationPlaylistFolder spf
                WHERE spf.station IN (
                  SELECT s FROM App\Entity\Station s
                  WHERE s.media_storage_location = :storageLocation
                )
                AND spf.path LIKE :path
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->setParameter('path', $dir . '%');

        return ReadWriteBatchIteratorAggregate::fromQuery($query, 25);
    }
}
