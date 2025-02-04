<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\MediaBatchResult;
use App\Entity\Interfaces\PathAwareInterface;
use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Entity\StorageLocation;
use App\Event\Radio\AnnotateNextSong;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use App\Message;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\LiquidsoapQueues;
use App\Utilities\File;
use App\Utilities\Time;
use App\Utilities\Types;
use Exception;
use InvalidArgumentException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

final class BatchAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly BatchUtilities $batchUtilities,
        private readonly MessageBus $messageBus,
        private readonly Adapters $adapters,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StationPlaylistMediaRepository $playlistMediaRepo,
        private readonly StationPlaylistFolderRepository $playlistFolderRepo,
        private readonly StationQueueRepository $queueRepo,
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $result = match (Types::string($request->getParam('do'))) {
            'delete' => $this->doDelete($request, $station, $storageLocation, $fsMedia),
            'playlist' => $this->doPlaylist($request, $station, $storageLocation, $fsMedia),
            'move' => $this->doMove($request, $station, $storageLocation, $fsMedia),
            'queue' => $this->doQueue($request, $station, $storageLocation, $fsMedia),
            'immediate' => $this->doPlayImmediately($request, $station, $storageLocation, $fsMedia),
            'reprocess' => $this->doReprocess($request, $station, $storageLocation, $fsMedia),
            'clear-extra' => $this->doClearExtra($request, $station, $storageLocation, $fsMedia),
            default => throw new InvalidArgumentException('Invalid batch action specified.')
        };

        if ($this->em->isOpen()) {
            $this->em->clear();
        }

        return $response->withJson($result);
    }

    private function doDelete(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        $successfulFiles = [];
        foreach ($result->files as $file) {
            try {
                $fs->delete($file);
                $successfulFiles[] = $file;
            } catch (UnableToDeleteFile $e) {
                $result->errors[] = sprintf('%s: %s', $file, $e->reason());
            } catch (Throwable $e) {
                $result->errors[] = sprintf('%s: %s', $file, $e->getMessage());
            }
        }

        $successfulDirs = [];
        foreach ($result->directories as $dir) {
            try {
                $fs->deleteDirectory($dir);
                $successfulDirs[] = $dir;
            } catch (UnableToDeleteDirectory $e) {
                $result->errors[] = sprintf('%s: %s', $dir, $e->reason());
            } catch (Throwable $e) {
                $result->errors[] = sprintf('%s: %s', $dir, $e->getMessage());
            }
        }

        $affectedPlaylistIds = $this->batchUtilities->handleDelete(
            $successfulFiles,
            $successfulDirs,
            $storageLocation,
            $fs
        );

        $this->writePlaylistChanges($station, $affectedPlaylistIds);

        return $result;
    }

    private function doPlaylist(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        /** @var array<int, int> $playlists */
        $playlists = [];

        /** @var array<int, int> $affectedPlaylistIds */
        $affectedPlaylistIds = [];

        /** @var string[] $requestPlaylists */
        $requestPlaylists = Types::array($request->getParam('playlists'));

        foreach ($requestPlaylists as $playlistId) {
            if ('new' === $playlistId) {
                $playlist = new StationPlaylist($station);
                $playlist->setName(
                    Types::string($request->getParam('new_playlist_name'))
                );

                $this->em->persist($playlist);
                $this->em->flush();

                $result->responseRecord = [
                    'id' => $playlist->getIdRequired(),
                    'name' => $playlist->getName(),
                ];

                $affectedPlaylistIds[$playlist->getIdRequired()] = $playlist->getIdRequired();
                $playlists[$playlist->getIdRequired()] = 0;
            } else {
                $playlist = $this->em->getRepository(StationPlaylist::class)->findOneBy(
                    [
                        'station_id' => $station->getIdRequired(),
                        'id' => (int)$playlistId,
                    ]
                );

                if ($playlist instanceof StationPlaylist) {
                    $affectedPlaylistIds[$playlist->getIdRequired()] = $playlist->getIdRequired();
                    $playlists[$playlist->getIdRequired()] = $this->playlistMediaRepo->getHighestSongWeight($playlist);
                }
            }
        }

        /*
         * NOTE: This iteration clears the entity manager.
         */
        foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
            try {
                $affectedPlaylistIds += $this->playlistMediaRepo->setPlaylistsForMedia(
                    $media,
                    $station,
                    $playlists
                );
            } catch (Exception $e) {
                $result->errors[] = $media->getPath() . ': ' . $e->getMessage();
            }
        }

        /** @var Station $station */
        $station = $this->em->refetch($station);

        foreach ($result->directories as $dir) {
            try {
                $this->playlistFolderRepo->setPlaylistsForFolder(
                    $station,
                    $dir,
                    $playlists
                );
            } catch (Exception $e) {
                $result->errors[] = $dir . ': ' . $e->getMessage();
            }
        }

        $this->em->flush();

        $this->writePlaylistChanges($station, $affectedPlaylistIds);

        return $result;
    }

    private function doMove(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs);

        $from = Types::string($request->getParam('currentDirectory'));
        $to = Types::string($request->getParam('directory'));

        $toMove = [
            $this->batchUtilities->iterateMedia($storageLocation, $result->files),
            $this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files),
        ];

        foreach ($toMove as $iterator) {
            foreach ($iterator as $record) {
                /** @var PathAwareInterface $record */
                $oldPath = $record->getPath();
                $newPath = File::renameDirectoryInPath($oldPath, $from, $to, false);

                try {
                    $fs->move($oldPath, $newPath);

                    $record->setPath($newPath);
                    $this->em->persist($record);
                } catch (Throwable $e) {
                    $result->errors[] = sprintf('%s: %s', $oldPath, $e->getMessage());
                }
            }
        }

        foreach ($result->directories as $dirPath) {
            $newDirPath = File::renameDirectoryInPath($dirPath, $from, $to);

            try {
                $fs->move($dirPath, $newDirPath);
            } catch (Throwable $e) {
                $result->errors[] = sprintf('%s: %s', $dirPath, $e->getMessage());
                continue;
            }

            $toMove = [
                $this->batchUtilities->iterateMediaInDirectory($storageLocation, $dirPath),
                $this->batchUtilities->iterateUnprocessableMediaInDirectory($storageLocation, $dirPath),
                $this->batchUtilities->iteratePlaylistFoldersInDirectory($storageLocation, $dirPath),
            ];

            foreach ($toMove as $iterator) {
                foreach ($iterator as $record) {
                    /** @var PathAwareInterface $record */
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
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        if ($station->useManualAutoDJ()) {
            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                /** @var Station $stationRef */
                $stationRef = $this->em->getReference(Station::class, $station->getId());

                $newRequest = new StationRequest($stationRef, $media, null, true);
                $this->em->persist($newRequest);
            }
        } else {
            $nextCuedItem = $this->queueRepo->getNextToSendToAutoDj($station);
            $cuedTimestamp = (null !== $nextCuedItem)
                ? $nextCuedItem->getTimestampCued()->subSeconds(10)
                : Time::nowUtc();

            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                try {
                    /** @var Station $stationRef */
                    $stationRef = $this->em->getReference(Station::class, $station->getId());

                    $newQueue = StationQueue::fromMedia($stationRef, $media);
                    $newQueue->setTimestampCued($cuedTimestamp);
                    $this->em->persist($newQueue);
                } catch (Throwable $e) {
                    $result->errors[] = sprintf('%s: %s', $media->getPath(), $e->getMessage());
                }

                $cuedTimestamp = $cuedTimestamp->subSeconds(10);
            }
        }

        return $result;
    }

    private function doPlayImmediately(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        if (BackendAdapters::Liquidsoap !== $station->getBackendType()) {
            throw new RuntimeException('This functionality can only be used on stations that use Liquidsoap.');
        }

        /** @var Liquidsoap $backend */
        $backend = $this->adapters->getBackendAdapter($station);

        if ($station->useManualAutoDJ()) {
            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                /** @var Station $station */
                $station = $this->em->find(Station::class, $station->getIdRequired());

                $event = AnnotateNextSong::fromStationMedia($station, $media, true);
                $this->eventDispatcher->dispatch($event);

                $backend->enqueue(
                    $station,
                    LiquidsoapQueues::Interrupting,
                    $event->buildAnnotations()
                );
            }
        } else {
            $cuedTimestamp = Time::nowUtc();

            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                try {
                    /** @var Station $station */
                    $station = $this->em->find(Station::class, $station->getIdRequired());

                    $newQueue = StationQueue::fromMedia($station, $media);
                    $newQueue->setTimestampCued($cuedTimestamp);
                    $newQueue->setIsPlayed();
                    $this->em->persist($newQueue);
                    $this->em->flush();

                    $event = AnnotateNextSong::fromStationQueue($newQueue, true);
                    $this->eventDispatcher->dispatch($event);

                    $backend->enqueue(
                        $station,
                        LiquidsoapQueues::Interrupting,
                        $event->buildAnnotations()
                    );
                } catch (Throwable $e) {
                    $result->errors[] = sprintf('%s: %s', $media->getPath(), $e->getMessage());
                }

                $cuedTimestamp = $cuedTimestamp->addSeconds(10);
            }
        }

        return $result;
    }

    private function doReprocess(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
            $mediaId = (int)$media->getId();

            $message = new Message\ReprocessMediaMessage();
            $message->storage_location_id = $storageLocation->getIdRequired();
            $message->media_id = $mediaId;
            $message->force = true;

            $this->messageBus->dispatch($message);
        }

        foreach ($this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files) as $unprocessable) {
            $message = new Message\AddNewMediaMessage();
            $message->storage_location_id = $storageLocation->getIdRequired();
            $message->path = $unprocessable->getPath();

            $this->messageBus->dispatch($message);
        }

        return $result;
    }

    private function doClearExtra(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
            $media->clearExtraMetadata();

            // Always flag for reprocessing to repopulate extra metadata from the file.
            $media->setMtime(0);

            $this->em->persist($media);
        }

        return $result;
    }

    private function parseRequest(
        ServerRequest $request,
        ExtendedFilesystemInterface $fs,
        bool $recursive = false
    ): MediaBatchResult {
        $files = array_values(
            Types::array($request->getParam('files', []))
        );
        $directories = array_values(
            Types::array($request->getParam('dirs', []))
        );

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

        $result = new MediaBatchResult();
        $result->files = $files;
        $result->directories = $directories;

        return $result;
    }

    private function writePlaylistChanges(
        Station $station,
        array $playlists
    ): void {
        // Write new PLS playlist configuration.
        if ($station->getBackendType()->isEnabled()) {
            foreach ($playlists as $playlistId => $playlistRow) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new Message\WritePlaylistFileMessage();
                $message->playlist_id = $playlistId;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
