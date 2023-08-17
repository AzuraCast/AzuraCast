<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\Api\Error;
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
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractStationApiCrudController<StationMedia> */
#[
    OA\Get(
        path: '/station/{station_id}/files',
        operationId: 'getFiles',
        description: 'List all current uploaded files.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Media'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/StationMedia')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/files',
        operationId: 'addFile',
        description: 'Upload a new file.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_UploadFile')
        ),
        tags: ['Stations: Media'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationMedia')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/file/{id}',
        operationId: 'getFile',
        description: 'Retrieve details for a single file.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Media'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationMedia')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/file/{id}',
        operationId: 'editFile',
        description: 'Update details of a single file.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationMedia')
        ),
        tags: ['Stations: Media'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/file/{id}',
        operationId: 'deleteFile',
        description: 'Delete a single file.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Media'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class FilesController extends AbstractStationApiCrudController
{
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
        $storageLocation = $this->getStation($request)->getMediaStorageLocation();

        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT e FROM App\Entity\StationMedia e
                WHERE e.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $this->getStation($request);

        $mediaStorage = $station->getMediaStorageLocation();
        if ($mediaStorage->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Error(500, __('This station is out of available storage space.')));
        }

        $request->getParsedBody();

        // Convert the body into an UploadFile API entity first.
        /** @var UploadFile $apiRecord */
        $apiRecord = $this->serializer->denormalize($request->getParsedBody(), UploadFile::class, null, []);

        // Validate the UploadFile API record.
        $errors = $this->validator->validate($apiRecord);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        // Write file to temp path.
        $tempPath = $station->getRadioTempDir() . '/' . $apiRecord->getSanitizedFilename();
        file_put_contents($tempPath, $apiRecord->getFileContents());

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

        $oldPath = $record->getPath();
        $isRenamed = (isset($data['path']) && $data['path'] !== $oldPath);

        $record = $this->fromArray($data, $record);

        if ($isRenamed) {
            $fsMedia->move($oldPath, $record->getPath());
        }

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        if ($record instanceof StationMedia) {
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
        }

        return $response->withJson(Status::updated());
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();
        $mediaStorage = $station->getMediaStorageLocation();

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

        $mediaStorage = $station->getMediaStorageLocation();
        $repo = $this->em->getRepository($this->entityClass);

        foreach (['id', 'unique_id', 'song_id'] as $field) {
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
        $row = parent::toArray($record, $context);

        if ($record instanceof StationMedia) {
            $row['custom_fields'] = $this->customFieldsRepo->getCustomFields($record);
        }
        return $row;
    }

    /**
     * @inheritDoc
     */
    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof StationMedia)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        // Delete the media file off the filesystem.
        // Write new PLS playlist configuration.
        foreach ($this->mediaRepo->remove($record, true) as $playlistId => $playlistRecord) {
            $playlist = $this->em->find(StationPlaylist::class, $playlistId);
            if (!($playlist instanceof StationPlaylist)) {
                continue;
            }

            $backend = $this->adapters->getBackendAdapter($playlist->getStation());
            if ($backend instanceof Liquidsoap) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new WritePlaylistFileMessage();
                $message->playlist_id = $playlistId;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
