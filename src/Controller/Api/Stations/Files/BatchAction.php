<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use App\Message;
use App\MessageQueue\QueueManagerInterface;
use App\Radio\Backend\Liquidsoap;
use App\Utilities\File;
use Azura\Files\ExtendedFilesystemInterface;
use Exception;
use InvalidArgumentException;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

class BatchAction
{
    public function __construct(
        protected BatchUtilities $batchUtilities,
        protected ReloadableEntityManagerInterface $em,
        protected MessageBus $messageBus,
        protected QueueManagerInterface $queueManager,
        protected Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        protected Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $result = match ($request->getParam('do')) {
            'delete' => $this->doDelete($request, $station, $storageLocation, $fsMedia),
            'playlist' => $this->doPlaylist($request, $station, $storageLocation, $fsMedia),
            'move' => $this->doMove($request, $station, $storageLocation, $fsMedia),
            'queue' => $this->doQueue($request, $station, $storageLocation, $fsMedia),
            'reprocess' => $this->doReprocess($request, $station, $storageLocation, $fsMedia),
            default => throw new InvalidArgumentException('Invalid batch action specified.')
        };

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
        ExtendedFilesystemInterface $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        foreach ($result->files as $file) {
            try {
                $fs->delete($file);
            } catch (Throwable $e) {
                $result->errors[] = $file . ': ' . $e->getMessage();
            }
        }

        foreach ($result->directories as $dir) {
            try {
                $fs->deleteDirectory($dir);
            } catch (Throwable $e) {
                $result->errors[] = $dir . ': ' . $e->getMessage();
            }
        }

        $affectedPlaylists = $this->batchUtilities->handleDelete(
            $result->files,
            $result->directories,
            $storageLocation,
            $fs
        );

        $this->writePlaylistChanges($request, $affectedPlaylists);

        return $result;
    }

    public function doPlaylist(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
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
        foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
            try {
                $mediaPlaylists = $this->playlistMediaRepo->clearPlaylistsFromMedia($media, $station);
                foreach ($mediaPlaylists as $playlistId => $playlistRecord) {
                    if (!isset($affectedPlaylists[$playlistId])) {
                        $affectedPlaylists[$playlistId] = $playlistRecord;
                    }
                }

                $this->em->flush();

                foreach ($playlists as $playlistRecord) {
                    /** @var Entity\StationPlaylist $playlist */
                    $playlist = $this->em->refetchAsReference($playlistRecord);

                    $playlistWeights[$playlist->getId()]++;
                    $weight = $playlistWeights[$playlist->getId()];

                    $this->playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);
                }
            } catch (Exception $e) {
                $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
                throw $e;
            }
        }

        /** @var Entity\Station $station */
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
        ExtendedFilesystemInterface $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs);

        $from = $request->getParam('currentDirectory', '');
        $to = $request->getParam('directory', '');

        $toMove = [
            $this->batchUtilities->iterateMedia($storageLocation, $result->files),
            $this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files),
        ];

        foreach ($toMove as $iterator) {
            foreach ($iterator as $record) {
                /** @var Entity\Interfaces\PathAwareInterface $record */
                $oldPath = $record->getPath();
                $newPath = File::renameDirectoryInPath($oldPath, $from, $to);

                try {
                    $fs->move($oldPath, $newPath);
                    $record->setPath($newPath);
                    $this->em->persist($record);
                } catch (Throwable $e) {
                    $result->errors[] = $oldPath . ': ' . $e->getMessage();
                }
            }
        }

        foreach ($result->directories as $dirPath) {
            $newDirPath = File::renameDirectoryInPath($dirPath, $from, $to);
            $fs->move($dirPath, $newDirPath);

            $toMove = [
                $this->batchUtilities->iterateMediaInDirectory($storageLocation, $dirPath),
                $this->batchUtilities->iterateUnprocessableMediaInDirectory($storageLocation, $dirPath),
                $this->batchUtilities->iteratePlaylistFoldersInDirectory($storageLocation, $dirPath),
            ];

            foreach ($toMove as $iterator) {
                foreach ($iterator as $record) {
                    /** @var Entity\Interfaces\PathAwareInterface $record */
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

        return $result;
    }

    public function doQueue(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
            try {
                /** @var Entity\Station $stationRef */
                $stationRef = $this->em->getReference(Entity\Station::class, $station->getId());

                $newQueue = Entity\StationQueue::fromMedia($stationRef, $media);
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
        ExtendedFilesystemInterface $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        // Get existing queue items
        $queuedMediaUpdates = [];
        $queuedNewFiles = [];

        foreach ($this->queueManager->getMessagesInTransport(QueueManagerInterface::QUEUE_MEDIA) as $message) {
            if ($message instanceof Message\ReprocessMediaMessage) {
                $queuedMediaUpdates[$message->media_id] = true;
            } elseif (
                $message instanceof Message\AddNewMediaMessage
                && $message->storage_location_id === $storageLocation->getId()
            ) {
                $queuedNewFiles[$message->path] = true;
            }
        }

        foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
            $mediaId = (int)$media->getId();

            if (!isset($queuedMediaUpdates[$mediaId])) {
                $message = new Message\ReprocessMediaMessage();
                $message->media_id = $mediaId;
                $message->force = true;

                $this->messageBus->dispatch($message);
            }
        }

        foreach ($this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files) as $unprocessable) {
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
        ExtendedFilesystemInterface $fs,
        bool $recursive = false
    ): Entity\Api\BatchResult {
        $files = array_values((array)$request->getParam('files', []));
        $directories = array_values((array)$request->getParam('dirs', []));

        if ($recursive) {
            foreach ($directories as $dir) {
                $dirIterator = $fs->listContents($dir, true)->filter(
                    function (StorageAttributes $attrs) {
                        return $attrs->isFile();
                    }
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
