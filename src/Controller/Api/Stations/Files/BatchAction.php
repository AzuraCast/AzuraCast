<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Exception;
use Jhofm\FlysystemIterator\Filter\FilterFactory;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

class BatchAction
{
    protected EntityManagerInterface $em;

    protected MessageBus $messageBus;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo;

    protected Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo;

    public function __construct(
        EntityManagerInterface $em,
        MessageBus $messageBus,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo
    ) {
        $this->em = $em;
        $this->messageBus = $messageBus;
        $this->mediaRepo = $mediaRepo;
        $this->playlistMediaRepo = $playlistMediaRepo;
        $this->playlistFolderRepo = $playlistFolderRepo;
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();
        $fs = $storageLocation->getFilesystem();

        switch ($request->getParam('do')) {
            case 'delete':
                $result = $this->doDelete($request, $station, $storageLocation, $fs);
                break;

            case 'playlist':
                $result = $this->doPlaylist($request, $station, $storageLocation, $fs);
                break;

            case 'move':
                $result = $this->doMove($request, $station, $storageLocation, $fs);
                break;

            case 'queue':
                $result = $this->doQueue($request, $station, $storageLocation, $fs);
                break;

            default:
                throw new \InvalidArgumentException('Invalid batch action specified.');
        }

        if ($this->em->isOpen()) {
            $this->em->clear(Entity\StationMedia::class);
            $this->em->clear(Entity\StationPlaylist::class);
            $this->em->clear(Entity\StationPlaylistMedia::class);
            $this->em->clear(Entity\StationRequest::class);
        }

        return $response->withJson($result);
    }

    public function doDelete(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        Filesystem $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        $affectedPlaylists = [];

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateMedia($storageLocation, $result->files) as $media) {
            try {
                $mediaPlaylists = $this->mediaRepo->remove($media, $fs);

                foreach ($mediaPlaylists as $playlistId => $playlist) {
                    if (!isset($affectedPlaylists[$playlistId])) {
                        $affectedPlaylists[$playlistId] = $playlist;
                    }
                }
            } catch (Throwable $e) {
                $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
            }
        }

        foreach ($result->files as $file) {
            try {
                $fs->delete($file);
            } catch (Throwable $e) {
                $errors[] = $file . ': ' . $e->getMessage();
            }
        }

        foreach ($result->directories as $dir) {
            try {
                $fs->deleteDir($dir);
            } catch (Throwable $e) {
                $errors[] = $dir . ': ' . $e->getMessage();
            }
        }

        $this->em->flush();

        $this->writePlaylistChanges($request, $affectedPlaylists);

        return $result;
    }

    public function doPlaylist(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        Filesystem $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        /** @var Entity\StationPlaylist[] $playlists */
        $playlists = [];
        $playlistWeights = [];
        $affectedPlaylists = [];

        foreach ($request->getParam('playlists') as $playlistId) {
            if ('new' === $playlistId) {
                $playlist = new Entity\StationPlaylist($station);
                $playlist->setName($request->getParam('new_playlist_name'));

                $this->em->persist($playlist);
                $this->em->flush();

                $result->responseRecord = [
                    'id' => $playlist->getId(),
                    'name' => $playlist->getName(),
                ];

                $affectedPlaylists[$playlist->getId()] = $playlist->getId();
                $playlists[$playlist->getId()] = $playlist;
                $playlistWeights[$playlist->getId()] = 0;
            } else {
                $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                    'station_id' => $station->getId(),
                    'id' => (int)$playlistId,
                ]);

                if ($playlist instanceof Entity\StationPlaylist) {
                    $affectedPlaylists[$playlist->getId()] = $playlist->getId();
                    $playlists[$playlist->getId()] = $playlist;
                    $playlistWeights[$playlist->getId()] = $this->playlistMediaRepo->getHighestSongWeight($playlist);
                }
            }
        }

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateMedia($storageLocation, $result->files) as $media) {
            try {
                $mediaPlaylists = $this->playlistMediaRepo->clearPlaylistsFromMedia($media);
                foreach ($mediaPlaylists as $playlistId => $playlistRecord) {
                    if (!isset($affectedPlaylists[$playlistId])) {
                        $affectedPlaylists[$playlistId] = $playlistRecord;
                    }
                }

                foreach ($playlists as $playlistId => $playlistRecord) {
                    /** @var Entity\StationPlaylist $playlist */
                    $playlist = $this->em->getReference(Entity\StationPlaylist::class, $playlistId);

                    $playlistWeights[$playlist->getId()]++;
                    $weight = $playlistWeights[$playlist->getId()];

                    $this->playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);
                }
            } catch (Exception $e) {
                $errors[] = $media->getPath() . ': ' . $e->getMessage();
                throw $e;
            }
        }

        /** @var Entity\Station $station */
        $station = $this->em->find(Entity\Station::class, $station->getId());

        foreach ($result->directories as $dir) {
            try {
                $this->playlistFolderRepo->setPlaylistsForFolder($station, $playlists, $dir);
            } catch (Exception $e) {
                $errors[] = $dir . ': ' . $e->getMessage();
            }
        }

        $this->em->flush();

        $this->writePlaylistChanges($request, $affectedPlaylists);

        return $result;
    }

    public function doMove(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        Filesystem $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, false);

        $from = $request->getParam('currentDirectory', '');
        $to = $request->getParam('directory', '');

        foreach ($this->iterateMedia($storageLocation, $result->files) as $media) {
            $oldPath = $media->getPath();
            $newPath = $this->renamePath($from, $to, $oldPath);

            try {
                if ($fs->rename($oldPath, $newPath)) {
                    $media->setPath($newPath);
                    $this->em->persist($media);
                }
            } catch (Throwable $e) {
                $result->errors[] = $oldPath . ': ' . $e->getMessage();
            }
        }

        foreach ($result->directories as $dirPath) {
            $newDirPath = $this->renamePath($from, $to, $dirPath);

            try {
                if ($fs->rename($dirPath, $newDirPath)) {
                    foreach ($this->iteratePlaylistFoldersInDirectory($station, $dirPath) as $playlistFolder) {
                        $playlistFolder->setPath($this->renamePath($from, $to, $playlistFolder->getPath()));
                        $this->em->persist($playlistFolder);
                    }

                    foreach ($this->iterateMediaInDirectory($storageLocation, $dirPath) as $media) {
                        $media->setPath($this->renamePath($from, $to, $media->getPath()));
                        $this->em->persist($media);
                    }
                }
            } catch (Throwable $e) {
                $result->errors[] = $dirPath . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    public function doQueue(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        Filesystem $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateMedia($storageLocation, $result->files) as $media) {
            try {
                /** @var Entity\Station $stationRef */
                $stationRef = $this->em->getReference(Entity\Station::class, $station->getId());

                $newQueue = new Entity\StationQueue($stationRef, $media);
                $newQueue->setMedia($media);
                $newQueue->setTimestampCued(time());

                $this->em->persist($newQueue);
            } catch (Throwable $e) {
                $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    protected function parseRequest(
        ServerRequest $request,
        Filesystem $fs,
        bool $recursive = false
    ): Entity\Api\BatchResult {
        $files = array_values((array)$request->getParam('files', []));
        $directories = array_values((array)$request->getParam('dirs', []));

        if ($recursive) {
            foreach ($directories as $dir) {
                $dirIterator = $fs->createIterator($dir, [
                    Options::OPTION_IS_RECURSIVE => true,
                    Options::OPTION_FILTER => FilterFactory::isFile(),
                ]);

                foreach ($dirIterator as $subDirMeta) {
                    $files[] = $subDirMeta['path'];
                }
            }
        }

        $result = new Entity\Api\BatchResult();
        $result->files = $files;
        $result->directories = $directories;

        return $result;
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
    protected function iterateMedia(Entity\StorageLocation $storageLocation, array $paths): iterable
    {
        $iteration = 0;

        $this->em->beginTransaction();

        try {
            foreach ($paths as $path) {
                $iteration++;

                $media = $this->mediaRepo->findByPath($path, $storageLocation);
                if ($media instanceof Entity\StationMedia) {
                    yield $path => $media;

                    if (!($iteration % 25)) {
                        $this->em->flush();
                        $this->em->clear();
                    }
                }
            }
        } catch (Throwable $exception) {
            $this->em->rollback();
            throw $exception;
        }

        $this->em->flush();
        $this->em->clear();
        $this->em->commit();
    }

    /**
     * @param Entity\StorageLocation $storageLocation
     * @param string $dir
     *
     * @return iterable|Entity\StationMedia[]
     */
    protected function iterateMediaInDirectory(Entity\StorageLocation $storageLocation, string $dir): iterable
    {
        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT sm 
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            AND sm.path LIKE :path')
            ->setParameter('storageLocation', $storageLocation)
            ->setParameter('path', $dir . '/%');

        return SimpleBatchIteratorAggregate::fromQuery($query, 25);
    }

    /**
     * @param Entity\Station $station
     * @param string $dir
     *
     * @return iterable|Entity\StationPlaylistFolder[]
     */
    protected function iteratePlaylistFoldersInDirectory(Entity\Station $station, string $dir): iterable
    {
        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT spf
            FROM App\Entity\StationPlaylistFolder spf
            WHERE spf.station = :station
            AND spf.path LIKE :path')
            ->setParameter('station', $station)
            ->setParameter('path', $dir . '%');

        return SimpleBatchIteratorAggregate::fromQuery($query, 25);
    }

    protected function renamePath(string $fromDir, string $toDir, string $path): string
    {
        if ('' === $fromDir && '' !== $toDir) {
            // Just prepend the new directory.
            return $toDir . '/' . $path;
        }

        if (0 === \stripos($path, $fromDir)) {
            $newBasePath = ltrim(substr($path, strlen($fromDir)), '/');
            if ('' !== $toDir) {
                return $toDir . '/' . $newBasePath;
            }
            return $newBasePath;
        }

        return $path;
    }

    protected function writePlaylistChanges(
        ServerRequest $request,
        array $playlists
    ): void {
        // Write new PLS playlist configuration.
        $backend = $request->getStationBackend();

        if ($backend instanceof Liquidsoap) {
            foreach ($playlists as $playlistId => $playlistRow) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new WritePlaylistFileMessage();
                $message->playlist_id = $playlistId;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
