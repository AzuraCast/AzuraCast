<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
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

/** @extends AbstractStationApiCrudController<Entity\StationMedia> */
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
    protected string $entityClass = Entity\StationMedia::class;
    protected string $resourceRouteName = 'api:stations:file';

    public function __construct(
        private readonly Adapters $adapters,
        private readonly MessageBus $messageBus,
        private readonly Entity\Repository\CustomFieldRepository $customFieldsRepo,
        private readonly Entity\Repository\StationMediaRepository $mediaRepo,
        private readonly Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        private readonly MediaProcessor $mediaProcessor,
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id
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
        string $station_id
    ): ResponseInterface {
        $station = $this->getStation($request);

        $mediaStorage = $station->getMediaStorageLocation();
        if ($mediaStorage->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('This station is out of available storage space.')));
        }

        $request->getParsedBody();

        // Convert the body into an UploadFile API entity first.
        /** @var Entity\Api\UploadFile $api_record */
        $api_record = $this->serializer->denormalize($request->getParsedBody(), Entity\Api\UploadFile::class, null, []);

        // Validate the UploadFile API record.
        $errors = $this->validator->validate($api_record);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        // Write file to temp path.
        $tempPath = $station->getRadioTempDir() . '/' . $api_record->getSanitizedFilename();
        file_put_contents($tempPath, $api_record->getFileContents());

        // Process temp path as regular media record.
        $record = $this->mediaProcessor->processAndUpload(
            $mediaStorage,
            $api_record->getSanitizedPath(),
            $tempPath
        );

        $return = (null !== $record)
            ? $this->viewRecord($record, $request)
            : Entity\Api\Status::success();

        return $response->withJson($return);
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $station = $this->getStation($request);
        $record = $this->getRecord($station, $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $custom_fields = $data['custom_fields'] ?? null;
        $playlists = $data['playlists'] ?? null;
        unset($data['custom_fields'], $data['playlists']);

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $record = $this->fromArray(
            $data,
            $record,
            [
                AbstractNormalizer::CALLBACKS => [
                    'path' => function ($new_value, $record) use ($fsMedia) {
                        // Detect and handle a rename.
                        if (($record instanceof Entity\StationMedia) && $new_value !== $record->getPath()) {
                            $fsMedia->move($record->getPath(), $new_value);
                        }

                        return $new_value;
                    },
                ],
            ]
        );

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        if ($record instanceof Entity\StationMedia) {
            $this->mediaRepo->writeToFile($record);
            $this->em->persist($record);
            $this->em->flush();

            if (null !== $custom_fields) {
                $this->customFieldsRepo->setCustomFields($record, $custom_fields);
            }

            if (null !== $playlists) {
                /** @var Entity\StationPlaylist[] $affected_playlists */
                $affected_playlists = [];

                // Remove existing playlists.
                $media_playlists = $this->playlistMediaRepo->clearPlaylistsFromMedia($record, $station);
                $this->em->flush();

                foreach ($media_playlists as $playlist_id => $playlist) {
                    if (!isset($affected_playlists[$playlist_id])) {
                        $affected_playlists[$playlist_id] = $playlist;
                    }
                }

                // Set new playlists.
                foreach ($playlists as $new_playlist) {
                    if (is_array($new_playlist)) {
                        $playlist_id = $new_playlist['id'];
                        $playlist_weight = $new_playlist['weight'] ?? 0;
                    } else {
                        $playlist_id = (int)$new_playlist;
                        $playlist_weight = 0;
                    }

                    $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy(
                        [
                            'station' => $station,
                            'id' => $playlist_id,
                        ]
                    );

                    if ($playlist instanceof Entity\StationPlaylist) {
                        $affected_playlists[$playlist->getId()] = $playlist;
                        $this->playlistMediaRepo->addMediaToPlaylist($record, $playlist, $playlist_weight);
                    }
                }

                $this->em->flush();

                // Handle playlist changes.
                $backend = $this->adapters->getBackendAdapter($station);
                if ($backend instanceof Liquidsoap) {
                    foreach ($affected_playlists as $playlist_id => $playlist_row) {
                        // Instruct the message queue to start a new "write playlist to file" task.
                        $message = new WritePlaylistFileMessage();
                        $message->playlist_id = $playlist_id;

                        $this->messageBus->dispatch($message);
                    }
                }
            }
        }

        return $response->withJson(Entity\Api\Status::updated());
    }

    protected function createRecord(array $data, Entity\Station $station): object
    {
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

    protected function getRecord(Entity\Station $station, int|string $id): ?object
    {
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

        if ($record instanceof Entity\StationMedia) {
            $row['custom_fields'] = $this->customFieldsRepo->getCustomFields($record);
        }
        return $row;
    }

    /**
     * @inheritDoc
     */
    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof Entity\StationMedia)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        // Delete the media file off the filesystem.
        // Write new PLS playlist configuration.
        foreach ($this->mediaRepo->remove($record, true) as $playlist_id => $playlist) {
            $backend = $this->adapters->getBackendAdapter($playlist->getStation());
            if ($backend instanceof Liquidsoap) {
                // Instruct the message queue to start a new "write playlist to file" task.
                $message = new WritePlaylistFileMessage();
                $message->playlist_id = $playlist_id;

                $this->messageBus->dispatch($message);
            }
        }
    }
}
