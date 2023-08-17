<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Configuration;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/** @extends AbstractApiCrudController<Station> */
#[
    OA\Get(
        path: '/admin/stations',
        operationId: 'adminGetStations',
        description: 'List all current stations in the system.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Stations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Station')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/admin/stations',
        operationId: 'adminAddStation',
        description: 'Create a new station.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Station')
        ),
        tags: ['Administration: Stations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Station')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/admin/station/{id}',
        operationId: 'adminGetStation',
        description: 'Retrieve details for a single station.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Stations'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Station')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/admin/station/{id}',
        operationId: 'adminEditStation',
        description: 'Update details of a single station.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Station')
        ),
        tags: ['Administration: Stations'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID',
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
        path: '/admin/station/{id}',
        operationId: 'adminDeleteStation',
        description: 'Delete a single station.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Stations'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID',
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
class StationsController extends AbstractApiCrudController
{
    use CanSortResults;

    protected string $entityClass = Station::class;
    protected string $resourceRouteName = 'api:admin:station';

    public function __construct(
        protected StationRepository $stationRepo,
        protected StorageLocationRepository $storageLocationRepo,
        protected StationQueueRepository $queueRepo,
        protected Configuration $configuration,
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
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Station::class, 'e');

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'name' => 'e.name',
            ],
            'e.name'
        );

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('(e.name LIKE :name OR e.short_name LIKE :name)')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links'] = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
            'manage' => $router->named(
                routeName: 'stations:index:index',
                routeParams: ['station_id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
            'clone' => $router->fromHere(
                routeName: 'api:admin:station:clone',
                routeParams: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
        ];

        return $return;
    }

    /**
     * @param Station $record
     * @param array<string, mixed> $context
     *
     * @return array<mixed>
     */
    protected function toArray(object $record, array $context = []): array
    {
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = [
            'adapter_api_key',
            'nowplaying',
            'nowplaying_timestamp',
            'automation_timestamp',
            'needs_restart',
            'has_started',
        ];

        foreach (Station::getStorageLocationTypes() as $locationKey => $storageLocationType) {
            $context[AbstractNormalizer::CALLBACKS][$locationKey] = static fn(
                StorageLocation $value
            ) => $value->getIdRequired();
        }

        return parent::toArray($record, $context);
    }

    protected function fromArray(array $data, ?object $record = null, array $context = []): object
    {
        foreach (Station::getStorageLocationTypes() as $locationKey => $storageLocationType) {
            $idKey = $locationKey . '_id';
            if (!empty($data[$idKey])) {
                $data[$locationKey] = $data[$idKey];
            }
            unset($data[$idKey]);
        }

        return parent::fromArray($data, $record, $context);
    }

    /**
     * @param array<mixed>|null $data
     * @param Station|null $record
     * @param array<string, mixed> $context
     *
     * @return Station
     */
    protected function editRecord(?array $data, object $record = null, array $context = []): object
    {
        $createMode = (null === $record);

        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $record = $this->fromArray($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        return ($createMode)
            ? $this->handleCreate($record)
            : $this->handleEdit($record);
    }

    /**
     * @param Station $record
     */
    protected function deleteRecord(object $record): void
    {
        $this->handleDelete($record);
    }

    protected function handleEdit(Station $station): Station
    {
        $originalRecord = $this->em->getUnitOfWork()->getOriginalEntityData($station);

        $this->em->persist($station);
        $this->em->flush();

        $this->configuration->initializeConfiguration($station);

        // Delete media-related items if the media storage is changed.
        /** @var StorageLocation|null $oldMediaStorage */
        $oldMediaStorage = $originalRecord['media_storage_location'];
        $newMediaStorage = $station->getMediaStorageLocation();

        if (null === $oldMediaStorage || $oldMediaStorage->getId() !== $newMediaStorage->getId()) {
            $this->stationRepo->flushRelatedMedia($station);
        }

        // If Manual AutoDJ mode is enabled, clear the queue.
        if ($station->useManualAutoDj()) {
            $this->queueRepo->clearUnplayed($station);
        }

        // Get the original values to check for changes.
        $oldFrontend = $originalRecord['frontend_type'];
        $oldBackend = $originalRecord['backend_type'];
        $oldHls = (bool)$originalRecord['enable_hls'];

        $frontendChanged = ($oldFrontend !== $station->getFrontendType());
        $backendChanged = ($oldBackend !== $station->getBackendType());
        $adapterChanged = $frontendChanged || $backendChanged;

        $hlsChanged = $oldHls !== $station->getEnableHls();

        if ($frontendChanged) {
            $this->stationRepo->resetMounts($station);
        }

        if ($hlsChanged || $backendChanged) {
            $this->stationRepo->resetHls($station);
        }

        if ($adapterChanged || !$station->getIsEnabled()) {
            try {
                $this->configuration->writeConfiguration(
                    station: $station,
                    forceRestart: true
                );
            } catch (Throwable) {
            }
        }

        return $station;
    }

    protected function handleCreate(Station $station): Station
    {
        $station->generateAdapterApiKey();

        $this->em->persist($station);
        $this->em->flush();

        try {
            // Initialize station folder configuration.
            $this->configuration->initializeConfiguration($station);

            // Create default mountpoints if station supports them.
            $this->stationRepo->resetMounts($station);
        } catch (Throwable $e) {
            $this->em->remove($station);
            $this->em->flush();

            throw $e;
        }

        return $station;
    }

    protected function handleDelete(Station $station): void
    {
        $this->configuration->removeConfiguration($station);

        // Remove directories generated specifically for this station.
        $directoriesToEmpty = [
            $station->getRadioConfigDir(),
            $station->getRadioPlaylistsDir(),
            $station->getRadioTempDir(),
        ];
        (new Filesystem())->remove($directoriesToEmpty);

        $this->em->flush();

        foreach ($station->getAllStorageLocations() as $storageLocation) {
            $stations = $this->storageLocationRepo->getStationsUsingLocation($storageLocation);
            if (1 === count($stations)) {
                $this->em->remove($storageLocation);
            }
        }

        $this->em->remove($station);
        $this->em->flush();
    }
}
