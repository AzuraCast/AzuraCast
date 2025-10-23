<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Cache\MediaListCache;
use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\MediaBatchResult;
use App\Entity\Interfaces\PathAwareInterface;
use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Entity\StorageLocation;
use App\Enums\StationPermissions;
use App\Event\Radio\AnnotateNextSong;
use App\Exception\Http\PermissionDeniedException;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use App\Message;
use App\OpenApi;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\LiquidsoapQueues;
use App\Utilities\File;
use App\Utilities\Time;
use App\Utilities\Types;
use Carbon\CarbonImmutable;
use Exception;
use InvalidArgumentException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

#[
    OA\Put(
        path: '/station/{station_id}/files/batch',
        operationId: 'putStationFileBatchAction',
        summary: 'Perform a batch action on a collection of files/directories.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO: API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
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
        private readonly StationFilesystems $stationFilesystems,
        private readonly MediaListCache $mediaListCache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $storageLocation = $station->media_storage_location;

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
        if (!$request->getAcl()->isAllowed(StationPermissions::DeleteMedia, $station)) {
            throw PermissionDeniedException::create($request);
        }

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

        $this->batchUtilities->handleDelete(
            $successfulFiles,
            $successfulDirs,
            $storageLocation,
            $fs
        );

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
                $playlist->name = Types::string($request->getParam('new_playlist_name'));

                $this->em->persist($playlist);
                $this->em->flush();

                $result->responseRecord = [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                ];

                $affectedPlaylistIds[$playlist->id] = $playlist->id;
                $playlists[$playlist->id] = 0;
            } else {
                $playlist = $this->em->getRepository(StationPlaylist::class)->findOneBy(
                    [
                        'station' => $station,
                        'id' => (int)$playlistId,
                    ]
                );

                if ($playlist instanceof StationPlaylist) {
                    $affectedPlaylistIds[$playlist->id] = $playlist->id;
                    $playlists[$playlist->id] = $this->playlistMediaRepo->getHighestSongWeight($playlist);
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
                $result->errors[] = $media->path . ': ' . $e->getMessage();
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

        $this->batchUtilities->writePlaylistChanges($affectedPlaylistIds);

        $this->mediaListCache->clearCache($storageLocation);

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

        $affectedPlaylists = [];

        $toMove = [
            $this->batchUtilities->iterateMedia($storageLocation, $result->files),
            $this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files),
        ];

        foreach ($toMove as $iterator) {
            foreach ($iterator as $record) {
                /** @var PathAwareInterface $record */
                $oldPath = $record->path;
                $newPath = File::renameDirectoryInPath($oldPath, $from, $to, false);

                try {
                    $fs->move($oldPath, $newPath);

                    $record->path = $newPath;
                    $this->em->persist($record);

                    if ($record instanceof StationMedia) {
                        $affectedPlaylists += $this->playlistMediaRepo->getPlaylistsForMedia($record);
                    }
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
                        $record->path = File::renameDirectoryInPath($record->path, $from, $to);
                        $this->em->persist($record);

                        if ($record instanceof StationMedia) {
                            $affectedPlaylists += $this->playlistMediaRepo->getPlaylistsForMedia($record);
                        } else {
                            if ($record instanceof StationPlaylistFolder) {
                                $playlist = $record->playlist;
                                $affectedPlaylists[$playlist->id] = $playlist->id;
                            }
                        }
                    } catch (Throwable $e) {
                        $result->errors[] = $record->path . ': ' . $e->getMessage();
                    }
                }
            }
        }

        $this->batchUtilities->writePlaylistChanges($affectedPlaylists);

        $this->mediaListCache->clearCache($storageLocation);

        return $result;
    }

    private function doQueue(
        ServerRequest $request,
        Station $station,
        StorageLocation $storageLocation,
        ExtendedFilesystemInterface $fs
    ): MediaBatchResult {
        $result = $this->parseRequest($request, $fs, true);

        if ($station->backend_config->use_manual_autodj) {
            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                /** @var Station $stationRef */
                $stationRef = $this->em->getReference(Station::class, $station->id);

                $newRequest = new StationRequest($stationRef, $media, null, true);
                $this->em->persist($newRequest);
            }
        } else {
            $nextCuedItem = $this->queueRepo->getNextToSendToAutoDj($station);
            $cuedTimestamp = (null !== $nextCuedItem)
                ? CarbonImmutable::instance($nextCuedItem->timestamp_cued)->subSeconds(10)
                : Time::nowUtc();

            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                try {
                    /** @var Station $stationRef */
                    $stationRef = $this->em->getReference(Station::class, $station->id);

                    $newQueue = StationQueue::fromMedia($stationRef, $media);
                    $newQueue->timestamp_cued = $cuedTimestamp;
                    $this->em->persist($newQueue);
                } catch (Throwable $e) {
                    $result->errors[] = sprintf('%s: %s', $media->path, $e->getMessage());
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

        if (BackendAdapters::Liquidsoap !== $station->backend_type) {
            throw new RuntimeException('This functionality can only be used on stations that use Liquidsoap.');
        }

        /** @var Liquidsoap $backend */
        $backend = $this->adapters->getBackendAdapter($station);

        if ($station->backend_config->use_manual_autodj) {
            foreach ($this->batchUtilities->iterateMedia($storageLocation, $result->files) as $media) {
                /** @var Station $station */
                $station = $this->em->find(Station::class, $station->id);

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
                    $station = $this->em->find(Station::class, $station->id);

                    $newQueue = StationQueue::fromMedia($station, $media);
                    $newQueue->timestamp_cued = $cuedTimestamp;
                    $newQueue->is_played = true;

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
                    $result->errors[] = sprintf('%s: %s', $media->path, $e->getMessage());
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
            $mediaId = (int)$media->id;

            $message = new Message\ReprocessMediaMessage();
            $message->storage_location_id = $storageLocation->id;
            $message->media_id = $mediaId;
            $message->force = true;

            $this->messageBus->dispatch($message);
        }

        foreach ($this->batchUtilities->iterateUnprocessableMedia($storageLocation, $result->files) as $unprocessable) {
            $message = new Message\AddNewMediaMessage();
            $message->storage_location_id = $storageLocation->id;
            $message->path = $unprocessable->path;

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
            $media->extra_metadata = null;

            // Always flag for reprocessing to repopulate extra metadata from the file.
            $media->mtime = 0;

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
}
