<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use App\Message;
use App\MessageQueue\QueueManagerInterface;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\LiquidsoapQueues;
use App\Utilities\File;
use App\Flysystem\ExtendedFilesystemInterface;
use Exception;
use InvalidArgumentException;
use League\Flysystem\StorageAttributes;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

final class BatchAction
{
    public function __construct(
        private readonly BatchUtilities $batchUtilities,
        private readonly ReloadableEntityManagerInterface $em,
        private readonly MessageBus $messageBus,
        private readonly QueueManagerInterface $queueManager,
        private readonly Adapters $adapters,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        private readonly Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo,
        private readonly Entity\Repository\StationQueueRepository $queueRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $result = match ($request->getParam('do')) {
            'delete' => $this->doDelete($request, $station, $storageLocation, $fsMedia),
            'playlist' => $this->doPlaylist($request, $station, $storageLocation, $fsMedia),
            'move' => $this->doMove($request, $station, $storageLocation, $fsMedia),
            'queue' => $this->doQueue($request, $station, $storageLocation, $fsMedia),
            'immediate' => $this->doPlayImmediately($request, $station, $storageLocation, $fsMedia),
            'reprocess' => $this->doReprocess($request, $station, $storageLocation, $fsMedia),
            default => throw new InvalidArgumentException('Invalid batch action specified.')
        };

        if ($this->em->isOpen()) {
            $this->em->clear();
        }

        return $response->withJson($result);
    }

    private function doDelete(
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

        $this->writePlaylistChanges($station, $affectedPlaylists);

        return $result;
    }

    private function doPlaylist(
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

        $this->writePlaylistChanges($station, $affectedPlaylists);

        return $result;
    }

    private function doMove(
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

    private function doQueue(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        if ($station->useManualAutoDJ()) {
            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                /** @var Entity\Station $stationRef */
                $stationRef = $this->em->getReference(Entity\Station::class, $station->getId());

                $newRequest = new Entity\StationRequest($stationRef, $media, null, true);
                $this->em->persist($newRequest);
            }
        } else {
            $nextCuedItem = $this->queueRepo->getNextToSendToAutoDj($station);
            $cuedTimestamp = (null !== $nextCuedItem)
                ? $nextCuedItem->getTimestampCued() - 10
                : time();

            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                try {
                    /** @var Entity\Station $stationRef */
                    $stationRef = $this->em->getReference(Entity\Station::class, $station->getId());

                    $newQueue = Entity\StationQueue::fromMedia($stationRef, $media);
                    $newQueue->setTimestampCued($cuedTimestamp);
                    $this->em->persist($newQueue);
                } catch (Throwable $e) {
                    $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
                }

                $cuedTimestamp -= 10;
            }
        }

        return $result;
    }

    private function doPlayImmediately(
        ServerRequest $request,
        Entity\Station $station,
        Entity\StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): Entity\Api\BatchResult {
        $result = $this->parseRequest($request, $fs, true);

        if (BackendAdapters::Liquidsoap !== $station->getBackendTypeEnum()) {
            throw new RuntimeException('This functionality can only be used on stations that use Liquidsoap.');
        }

        /** @var Liquidsoap $backend */
        $backend = $this->adapters->getBackendAdapter($station);

        if ($station->useManualAutoDJ()) {
            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                /** @var Entity\Station $station */
                $station = $this->em->find(Entity\Station::class, $station->getIdRequired());

                $event = AnnotateNextSong::fromStationMedia($station, $media, true);
                $this->eventDispatcher->dispatch($event);

                $backend->enqueue(
                    $station,
                    LiquidsoapQueues::Interrupting,
                    $event->buildAnnotations()
                );
            }
        } else {
            $cuedTimestamp = time();

            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                try {
                    /** @var Entity\Station $station */
                    $station = $this->em->find(Entity\Station::class, $station->getIdRequired());

                    $newQueue = Entity\StationQueue::fromMedia($station, $media);
                    $newQueue->setTimestampCued($cuedTimestamp);
                    $newQueue->setIsPlayed();
                    $this->em->persist($newQueue);

                    $event = AnnotateNextSong::fromStationQueue($newQueue, true);
                    $this->eventDispatcher->dispatch($event);

                    $backend->enqueue(
                        $station,
                        LiquidsoapQueues::Interrupting,
                        $event->buildAnnotations()
                    );
                } catch (Throwable $e) {
                    $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
                }

                $cuedTimestamp += 10;
            }
        }

        return $result;
    }

    private function doReprocess(
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
                $message->storage_location_id = $storageLocation->getIdRequired();
                $message->media_id = $mediaId;
                $message->force = true;

                $this->messageBus->dispatch($message);
            }
        }

        foreach ($this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files) as $unprocessable) {
            $path = $unprocessable->getPath();

            if (!isset($queuedNewFiles[$path])) {
                $message = new Message\AddNewMediaMessage();
                $message->storage_location_id = $storageLocation->getIdRequired();
                $message->path = $unprocessable->getPath();

                $this->messageBus->dispatch($message);
            }
        }

        return $result;
    }

    private function parseRequest(
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

    private function writePlaylistChanges(
        Entity\Station $station,
        array $playlists
    ): void {
        // Write new PLS playlist configuration.
        if ($station->getBackendTypeEnum()->isEnabled()) {
            foreach ($playlists as $playlistId => $playlistRow) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new Message\WritePlaylistFileMessage();
                $message->playlist_id = $playlistId;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
