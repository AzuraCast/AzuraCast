<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Api\Error;
use App\Entity\Api\StationMedia as ApiStationMedia;
use App\Entity\Api\StationMediaPlaylist;
use App\Entity\Api\Status;
use App\Entity\Api\UploadFile;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Exception\ValidationException;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\MediaProcessor;
use App\Message\WritePlaylistFileMessage;
use App\OpenApi;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractStationApiCrudController<StationMedia> */
#[
    OA\Get(
        path: '/station/{station_id}/files',
        operationId: 'getFiles',
        summary: 'List all current uploaded files.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: ApiStationMedia::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/files',
        operationId: 'addFile',
        summary: 'Upload a new file.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: UploadFile::class)
        ),
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: ApiStationMedia::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/file/{id}',
        operationId: 'getFile',
        summary: 'Retrieve details for a single file.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'media_id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: ApiStationMedia::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/file/{id}',
        operationId: 'editFile',
        summary: 'Update details of a single file.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiStationMedia::class)
        ),
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'media_id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/file/{id}',
        operationId: 'deleteFile',
        summary: 'Delete a single file.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'media_id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class FilesController extends AbstractStationApiCrudController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = StationMedia::class;
    protected string $resourceRouteName = 'api:stations:file';

    public function __construct(
        private readonly Adapters $adapters,
        private readonly MessageBus $messageBus,
        private readonly CustomFieldRepository $customFieldsRepo,
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationPlaylistMediaRepository $playlistMediaRepo,
        private readonly MediaProcessor $mediaProcessor,
        private readonly StationFilesystems $stationFilesystems,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $storageLocation = $this->getStation($request)->media_storage_location;

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(StationMedia::class, 'e')
            ->where('e.storage_location = :storageLocation')
            ->setParameter('storageLocation', $storageLocation);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'path' => 'e.path',
                'title' => 'e.title',
                'artist' => 'e.artist',
                'album' => 'e.album',
                'genre' => 'e.genre',
                'length' => 'e.length',
                'mtime' => 'e.mtime',
            ],
            'e.path'
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.title',
                'e.artist',
                'e.path',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): ApiStationMedia
    {
        $returnArray = $this->toArray($record);

        $playlists = array_map(
            fn(array $row) => new StationMediaPlaylist(
                id: $row['id'],
                name: $row['name'],
                short_name: StationPlaylist::generateShortName($row['name']),
                folder: $row['folder']
            ),
            $returnArray['playlists'] ?? []
        );

        $return = ApiStationMedia::fromArray(
            $returnArray,
            $record->extra_metadata->toArray() ?? [],
            $this->customFieldsRepo->getCustomFields($record),
            StationMediaPlaylist::aggregate($playlists),
        );

        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $routeParams = [
            'media_id' => $record->unique_id,
        ];

        if (0 !== $record->art_updated_at) {
            $routeParams['timestamp'] = $record->art_updated_at;
        }

        $return->art = $router->fromHere(
            'api:stations:media:art',
            routeParams: $routeParams,
            absolute: !$isInternal
        );

        $return->links = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'play' => $router->fromHere(
                'api:stations:files:play',
                ['id' => $record->id],
                absolute: true
            ),
            'art' => $router->fromHere(
                'api:stations:media:art',
                ['media_id' => $record->id],
                absolute: !$isInternal
            ),
            'waveform' => $router->fromHere(
                'api:stations:media:waveform',
                [
                    'media_id' => $record->unique_id,
                    'timestamp' => $record->art_updated_at,
                ],
                absolute: !$isInternal
            ),
            'waveform_cache' => $router->fromHere(
                'api:stations:media:waveform-cache',
                [
                    'media_id' => $record->unique_id,
                ],
                absolute: !$isInternal
            ),
        ];

        return $return;
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $this->getStation($request);

        $mediaStorage = $station->media_storage_location;
        if ($mediaStorage->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Error(500, __('This station is out of available storage space.')));
        }

        $request->getParsedBody();

        // Convert the body into an UploadFile API entity first.
        /** @var UploadFile $apiRecord */
        $apiRecord = $this->serializer->denormalize($request->getParsedBody(), UploadFile::class);

        // Validate the UploadFile API record.
        $errors = $this->validator->validate($apiRecord);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        // Write file to temp path.
        $tempPath = $station->getRadioTempDir() . '/' . $apiRecord->getSanitizedFilename();
        new Filesystem()->dumpFile($tempPath, $apiRecord->getFileContents());

        // Process temp path as regular media record.
        $record = $this->mediaProcessor->processAndUpload(
            $mediaStorage,
            $apiRecord->getSanitizedPath(),
            $tempPath
        );

        $return = (null !== $record)
            ? $this->viewRecord($record, $request)
            : Status::success();

        return $response->withJson($return);
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $this->getStation($request);
        $record = $this->getRecord($request, $params);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $customFields = $data['custom_fields'] ?? null;
        $playlists = $data['playlists'] ?? null;
        unset($data['custom_fields'], $data['playlists']);

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $oldPath = $record->path;
        $isRenamed = (isset($data['path']) && $data['path'] !== $oldPath);

        $record = $this->fromArray($data, $record);

        if ($isRenamed) {
            $fsMedia->move($oldPath, $record->path);
        }

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        $this->mediaRepo->writeToFile($record);
        $this->em->persist($record);
        $this->em->flush();

        if (null !== $customFields) {
            $this->customFieldsRepo->setCustomFields($record, $customFields);
        }

        if (null !== $playlists) {
            $playlistsToAssign = [];
            foreach ($playlists as $newPlaylist) {
                if (is_array($newPlaylist)) {
                    $playlistsToAssign[(int)$newPlaylist['id']] = $newPlaylist['weight'] ?? 0;
                } else {
                    $playlistsToAssign[(int)$newPlaylist] = 0;
                }
            }

            $affectedPlaylistIds = $this->playlistMediaRepo->setPlaylistsForMedia(
                $record,
                $station,
                $playlistsToAssign
            );

            // Handle playlist changes.
            $backend = $this->adapters->getBackendAdapter($station);
            if ($backend instanceof Liquidsoap) {
                foreach ($affectedPlaylistIds as $playlistId => $playlistRow) {
                    // Instruct the message queue to start a new "write playlist to file" task.
                    $message = new WritePlaylistFileMessage();
                    $message->playlist_id = $playlistId;

                    $this->messageBus->dispatch($message);
                }
            }
        }

        return $response->withJson(Status::updated());
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();
        $mediaStorage = $station->media_storage_location;

        return $this->editRecord(
            $data,
            null,
            [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    $this->entityClass => [
                        'station' => $station,
                        'storageLocation' => $mediaStorage,
                    ],
                ],
            ]
        );
    }

    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $mediaStorage = $station->media_storage_location;
        $repo = $this->em->getRepository($this->entityClass);

        foreach (['id', 'unique_id', 'song_id'] as $field) {
            if ($field === 'id' && !is_numeric($id)) {
                continue;
            }

            $record = $repo->findOneBy(
                [
                    'storage_location' => $mediaStorage,
                    $field => $id,
                ]
            );

            if ($record instanceof $this->entityClass) {
                return $record;
            }
        }

        return null;
    }

    /** @inheritDoc */
    protected function toArray(object $record, array $context = []): array
    {
        return [
            ...parent::toArray($record, $context),
            'custom_fields' => $this->customFieldsRepo->getCustomFields($record),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function deleteRecord(object $record): void
    {
        // Delete the media file off the filesystem.
        // Write new PLS playlist configuration.
        foreach ($this->mediaRepo->remove($record, true) as $playlistId => $playlistRecord) {
            $playlist = $this->em->find(StationPlaylist::class, $playlistId);
            if (!($playlist instanceof StationPlaylist)) {
                continue;
            }

            $backend = $this->adapters->getBackendAdapter($playlist->station);
            if ($backend instanceof Liquidsoap) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new WritePlaylistFileMessage();
                $message->playlist_id = $playlistId;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
