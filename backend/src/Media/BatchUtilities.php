<?php

declare(strict_types=1);

namespace App\Media;

use App\Cache\MediaListCache;
use App\Container\EntityManagerAwareTrait;
use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Repository\UnprocessableMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylistFolder;
use App\Entity\StorageLocation;
use App\Entity\UnprocessableMedia;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Message\WritePlaylistFileMessage;
use App\Utilities\File;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

final class BatchUtilities
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly MessageBus $messageBus,
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly UnprocessableMediaRepository $unprocessableMediaRepo,
        private readonly StorageLocationRepository $storageLocationRepo,
        private readonly MediaListCache $mediaListCache
    ) {
    }

    public function handleRename(
        string $from,
        string $to,
        StorageLocation $storageLocation,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $affectedPlaylists = [];

        if ($fs->directoryExists($to)) {
            // Update the paths of all media contained within the directory.
            foreach ($this->iterateMediaInDirectory($storageLocation, $from) as $record) {
                $record->path = File::renameDirectoryInPath($record->path, $from, $to);
                $this->em->persist($record);

                $affectedPlaylists += $this->spmRepo->getPlaylistsForMedia($record);
            }

            foreach ($this->iterateUnprocessableMediaInDirectory($storageLocation, $from) as $record) {
                $record->path = File::renameDirectoryInPath($record->path, $from, $to);
                $this->em->persist($record);
            }

            foreach ($this->iteratePlaylistFoldersInDirectory($storageLocation, $from) as $record) {
                $record->path = File::renameDirectoryInPath($record->path, $from, $to);
                $this->em->persist($record);

                $playlist = $record->playlist;
                $affectedPlaylists[$playlist->id] = $playlist->id;
            }
        } else {
            $record = $this->mediaRepo->findByPath($from, $storageLocation);

            if ($record instanceof StationMedia) {
                $affectedPlaylists += $this->spmRepo->getPlaylistsForMedia($record);

                $record->path = $to;
                $this->em->persist($record);
                $this->em->flush();
            } else {
                $record = $this->unprocessableMediaRepo->findByPath($from, $storageLocation);

                if ($record instanceof UnprocessableMedia) {
                    $record->path = $to;
                    $this->em->persist($record);
                    $this->em->flush();
                }
            }
        }

        $this->writePlaylistChanges($affectedPlaylists);

        $this->mediaListCache->clearCache($storageLocation);
    }

    public function handleDelete(
        array $files,
        array $directories,
        StorageLocation $storageLocation,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
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

        $this->writePlaylistChanges($affectedPlaylists);

        $this->mediaListCache->clearCache($storageLocation);
    }

    /**
     * Iterate through the found media records, while occasionally flushing and clearing the entity manager.
     *
     * @note This function flushes the entity manager.
     *
     * @param StorageLocation $storageLocation
     * @param array $paths
     *
     * @return iterable<StationMedia>
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
     * @return iterable<StationMedia>
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
     * @return iterable<UnprocessableMedia>
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
     * @return iterable<UnprocessableMedia>
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
     * @return iterable<StationPlaylistFolder>
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

    /**
     * @param int[] $playlists
     * @return void
     */
    public function writePlaylistChanges(
        array $playlists
    ): void {
        foreach (array_unique($playlists) as $playlistId) {
            // Instruct the message queue to start a new "write playlist to file" task.
            $message = new WritePlaylistFileMessage();
            $message->playlist_id = $playlistId;

            $this->messageBus->dispatch($message);
        }
    }
}
