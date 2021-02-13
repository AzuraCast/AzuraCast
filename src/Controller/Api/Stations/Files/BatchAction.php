<?php

namespace App\Controller\Api\Stations\Files;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message;
use App\MessageQueue\QueueManager;
use App\Radio\Backend\Liquidsoap;
use App\Utilities\File;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Exception;
use Jhofm\FlysystemIterator\Filter\FilterFactory;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

class BatchAction
{
    protected ReloadableEntityManagerInterface $em;

    protected MessageBus $messageBus;

    protected QueueManager $queueManager;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo;

    protected Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo;

    protected Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        MessageBus $messageBus,
        QueueManager $queueManager,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo,
        Entity\Repository\UnprocessableMediaRepository $unprocessableMediaRepo
    ) {
        $this->em = $em;
        $this->messageBus = $messageBus;
        $this->queueManager = $queueManager;

        $this->mediaRepo = $mediaRepo;
        $this->playlistMediaRepo = $playlistMediaRepo;
        $this->playlistFolderRepo = $playlistFolderRepo;
        $this->unprocessableMediaRepo = $unprocessableMediaRepo;
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

            case 'reprocess':
                $result = $this->doReprocess($request, $station, $storageLocation, $fs);
                break;

            default:
                throw new \InvalidArgumentException('Invalid batch action specified.');
        }

        if ($this->em->isOpen()) {
            $this->em->clear(Entity\StationMedia::class);
            $this->em->clear(Entity\StationPlaylist::class);
            $this->em->clear(Entity\StationPlaylistMedia::class);
            $this->em->clear(Entity\UnprocessableMedia::class);
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
                $mediaPlaylists = $this->mediaRepo->remove($media, false, $fs);

                foreach ($mediaPlaylists as $playlistId => $playlist) {
                    if (!isset($affectedPlaylists[$playlistId])) {
                        $affectedPlaylists[$playlistId] = $playlist;
                    }
                }
            } catch (Throwable $e) {
                $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
            }
        }

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->iterateUnprocessableMedia($storageLocation, $result->files) as $unprocessableMedia) {
            $this->em->remove($unprocessableMedia);
        }

        foreach ($result->files as $file) {
            try {
                $fs->delete($file);
            } catch (Throwable $e) {
                $result->errors[] = $file . ': ' . $e->getMessage();
            }
        }

        foreach ($result->directories as $dir) {
            foreach ($this->iteratePlaylistFoldersInDirectory($station, $dir) as $playlistFolder) {
                $this->em->remove($playlistFolder);
            }

            try {
                $fs->deleteDir($dir);
            } catch (Throwable $e) {
                $result->errors[] = $dir . ': ' . $e->getMessage();
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
                $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy(
                    [
                        'station_id' => $station->getId(),
                        'id' => (int)$playlistId,
                    ]
                );

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
                $mediaPlaylists = $this->playlistMediaRepo->clearPlaylistsFromMedia($media, $station);
                foreach ($mediaPlaylists as $playlistId => $playlistRecord) {
                    if (!isset($affectedPlaylists[$playlistId])) {
                        $affectedPlaylists[$playlistId] = $playlistRecord;
                    }
                }

                $this->em->flush();

                foreach ($playlists as $playlistId => $playlistRecord) {
                    $playlist = $this->em->refetchAsReference($playlistRecord);

                    $playlistWeights[$playlist->getId()]++;
                    $weight = $playlistWeights[$playlist->getId()];

                    $this->playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);
                }
            } catch (Exception $e) {
                $errors[] = $media->getPath() . ': ' . $e->getMessage();
                throw $e;
            }
        }

        $station = $this->em->refetch($station);

        foreach ($result->directories as $dir) {
            try {
                $this->playlistFolderRepo->setPlaylistsForFolder($station, $playlists, $dir);
            } catch (Exception $e) {
                $result->errors[] = $dir . ': ' . $e->getMessage();
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

        $toMove = [
            $this->iterateMedia($storageLocation, $result->files),
            $this->iterateUnprocessableMedia($storageLocation, $result->files),
        ];

        foreach ($toMove as $iterator) {
            foreach ($iterator as $record) {
                /** @var Entity\PathAwareInterface $record */
                $oldPath = $record->getPath();
                $newPath = File::renameDirectoryInPath($oldPath, $from, $to);

                try {
                    if ($fs->rename($oldPath, $newPath)) {
                        $record->setPath($newPath);
                        $this->em->persist($record);
                    }
                } catch (Throwable $e) {
                    $result->errors[] = $oldPath . ': ' . $e->getMessage();
                }
            }
        }

        foreach ($result->directories as $dirPath) {
            $newDirPath = File::renameDirectoryInPath($dirPath, $from, $to);

            if ($fs->rename($dirPath, $newDirPath)) {
                $toMove = [
                    $this->iterateMediaInDirectory($storageLocation, $dirPath),
                    $this->iterateUnprocessableMediaInDirectory($storageLocation, $dirPath),
                    $this->iteratePlaylistFoldersInDirectory($station, $dirPath),
                ];

                foreach ($toMove as $iterator) {
                    foreach ($iterator as $record) {
                        /** @var Entity\PathAwareInterface $record */
                        try {
                            $record->setPath(
                                File::renameDirectoryInPath($record->getPath(), $from, $to)
                            );
                            $this->em->persist($record);
                        } catch (Throwable $e) {
                            $result->errors[] = $record->getPath() . ': ' . $e->getMessage();
                        }
                    }
                }
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

    public function doReprocess(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        Filesystem $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        // Get existing queue items
        $queuedMediaUpdates = [];
        $queuedNewFiles = [];

        foreach ($this->queueManager->getMessagesInTransport(QueueManager::QUEUE_MEDIA) as $message) {
            if ($message instanceof Message\ReprocessMediaMessage) {
                $queuedMediaUpdates[$message->media_id] = true;
            } elseif (
                $message instanceof Message\AddNewMediaMessage
                && $message->storage_location_id === $storageLocation->getId()
            ) {
                $queuedNewFiles[$message->path] = true;
            }
        }

        foreach ($this->iterateMedia($storageLocation, $result->files) as $media) {
            $mediaId = (int)$media->getId();

            if (!isset($queuedMediaUpdates[$mediaId])) {
                $message = new Message\ReprocessMediaMessage();
                $message->media_id = $mediaId;
                $message->force = true;

                $this->messageBus->dispatch($message);
            }
        }

        foreach ($this->iterateUnprocessableMedia($storageLocation, $result->files) as $unprocessable) {
            $path = $unprocessable->getPath();

            if (!isset($queuedNewFiles[$path])) {
                $message = new Message\AddNewMediaMessage();
                $message->storage_location_id = (int)$storageLocation->getId();
                $message->path = $unprocessable->getPath();

                $this->messageBus->dispatch($message);
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
                $dirIterator = $fs->createIterator(
                    $dir,
                    [
                        Options::OPTION_IS_RECURSIVE => true,
                        Options::OPTION_FILTER => FilterFactory::isFile(),
                    ]
                );

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
        return SimpleBatchIteratorAggregate::fromTraversableResult(
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
    protected function iterateMediaInDirectory(Entity\StorageLocation $storageLocation, string $dir): iterable
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

        return SimpleBatchIteratorAggregate::fromQuery($query, 25);
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
    protected function iterateUnprocessableMedia(Entity\StorageLocation $storageLocation, array $paths): iterable
    {
        return SimpleBatchIteratorAggregate::fromTraversableResult(
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
    protected function iterateUnprocessableMediaInDirectory(
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
        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT spf
                FROM App\Entity\StationPlaylistFolder spf
                WHERE spf.station = :station
                AND spf.path LIKE :path
            DQL
        )->setParameter('station', $station)
            ->setParameter('path', $dir . '%');

        return SimpleBatchIteratorAggregate::fromQuery($query, 25);
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
                $message = new Message\WritePlaylistFileMessage();
                $message->playlist_id = $playlistId;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
