<?php

declare(strict_types=1);

namespace App\Media;

use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity;
use App\Utilities\File;
use Azura\Files\ExtendedFilesystemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

class BatchUtilities
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationMediaRepository $mediaRepo,
        protected Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo,
    ) {
    }

    public function handleRename(
        string $from,
        string $to,
        Entity\StorageLocation $storageLocation,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $storageLocation->getFilesystem();

        if ($fs->isDir($to)) {
            // Update the paths of all media contained within the directory.
            $toRename = [
                $this->iterateMediaInDirectory($storageLocation, $from),
                $this->iterateUnprocessableMediaInDirectory($storageLocation, $from),
                $this->iteratePlaylistFoldersInDirectory($storageLocation, $from),
            ];

            foreach ($toRename as $iterator) {
                foreach ($iterator as $record) {
                    /** @var Entity\Interfaces\PathAwareInterface $record */
                    $record->setPath(
                        File::renameDirectoryInPath($record->getPath(), $from, $to)
                    );
                    $this->em->persist($record);
                }
            }
        } else {
            $record = $this->mediaRepo->findByPath($from, $storageLocation);

            if ($record instanceof Entity\StationMedia) {
                $record->setPath($to);
                $this->em->persist($record);
                $this->em->flush();
            } else {
                $record = $this->unprocessableMediaRepo->findByPath($from, $storageLocation);

                if ($record instanceof Entity\UnprocessableMedia) {
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
     * @param Entity\StorageLocation $storageLocation
     * @param ExtendedFilesystemInterface|null $fs
     *
     * @return Entity\StationPlaylist[] Affected playlists
     */
    public function handleDelete(
        array $files,
        array $directories,
        Entity\StorageLocation $storageLocation,
        ?ExtendedFilesystemInterface $fs = null
    ): array {
        $fs ??= $storageLocation->getFilesystem();
        $affectedPlaylists = [];

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateMedia($storageLocation, $files) as $media) {
            try {
                foreach ($this->mediaRepo->remove($media, false, $fs) as $playlistId => $playlist) {
                    if (!isset($affectedPlaylists[$playlistId])) {
                        $affectedPlaylists[$playlistId] = $playlist;
                    }
                }
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
     * @param Entity\StorageLocation $storageLocation
     * @param array $paths
     *
     * @return iterable|Entity\StationMedia[]
     */
    public function iterateMedia(Entity\StorageLocation $storageLocation, array $paths): iterable
    {
        return ReadWriteBatchIteratorAggregate::fromTraversableResult(
            $this->mediaRepo->iteratePaths($paths, $storageLocation),
            $this->em,
            25
        );
    }

    /**
     * @param Entity\StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|Entity\StationMedia[]
     */
    public function iterateMediaInDirectory(Entity\StorageLocation $storageLocation, string $dir): iterable
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
     * @param Entity\StorageLocation $storageLocation
     * @param array $paths
     *
     * @return iterable|Entity\UnprocessableMedia[]
     */
    public function iterateUnprocessableMedia(Entity\StorageLocation $storageLocation, array $paths): iterable
    {
        return ReadWriteBatchIteratorAggregate::fromTraversableResult(
            $this->unprocessableMediaRepo->iteratePaths($paths, $storageLocation),
            $this->em,
            25
        );
    }

    /**
     * @param Entity\StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|Entity\UnprocessableMedia[]
     */
    public function iterateUnprocessableMediaInDirectory(
        Entity\StorageLocation $storageLocation,
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
     * @param Entity\StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|Entity\StationPlaylistFolder[]
     */
    public function iteratePlaylistFoldersInDirectory(
        Entity\StorageLocation $storageLocation,
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
