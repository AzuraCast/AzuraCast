<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\Traits\CanSortResults;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
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

/** @extends AbstractAdminApiCrudController<Entity\Station> */
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
class StationsController extends AbstractAdminApiCrudController
{
    use CanSortResults;

    protected string $entityClass = Entity\Station::class;
    protected string $resourceRouteName = 'api:admin:station';

    public function __construct(
        protected Entity\Repository\StationRepository $stationRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected Configuration $configuration,
        protected ReloadableEntityManagerInterface $reloadableEm,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($reloadableEm, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Entity\Station::class, 'e');

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
     * @param Entity\Station $record
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

        foreach (Entity\Station::getStorageLocationTypes() as $locationKey => $storageLocationType) {
            $context[AbstractNormalizer::CALLBACKS][$locationKey] = static fn(
                array $value
            ) => $value['id'];
        }

        return parent::toArray($record, $context);
    }

    protected function fromArray(array $data, ?object $record = null, array $context = []): object
    {
        foreach (Entity\Station::getStorageLocationTypes() as $locationKey => $storageLocationType) {
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
     * @param Entity\Station|null $record
     * @param array<string, mixed> $context
     *
     * @return Entity\Station
     */
    protected function editRecord(?array $data, object $record = null, array $context = []): object
    {
        $create_mode = (null === $record);

        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $record = $this->fromArray($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        return ($create_mode)
            ? $this->handleCreate($record)
            : $this->handleEdit($record);
    }

    /**
     * @param Entity\Station $record
     */
    protected function deleteRecord(object $record): void
    {
        $this->handleDelete($record);
    }

    protected function handleEdit(Entity\Station $station): Entity\Station
    {
        $original_record = $this->em->getUnitOfWork()->getOriginalEntityData($station);

        $this->em->persist($station);
        $this->em->flush();

        $this->configuration->initializeConfiguration($station);

        // Delete media-related items if the media storage is changed.
        /** @var Entity\StorageLocation|null $oldMediaStorage */
        $oldMediaStorage = $original_record['media_storage_location'];
        $newMediaStorage = $station->getMediaStorageLocation();

        if (null === $oldMediaStorage || $oldMediaStorage->getId() !== $newMediaStorage->getId()) {
            $this->stationRepo->flushRelatedMedia($station);
        }

        // If Manual AutoDJ mode is enabled, clear the queue.
        if ($station->useManualAutoDj()) {
            $this->queueRepo->clearUnplayed($station);
        }

        // Get the original values to check for changes.
        $old_frontend = $original_record['frontend_type'];
        $old_backend = $original_record['backend_type'];
        $old_hls = (bool)$original_record['enable_hls'];

        $frontend_changed = ($old_frontend !== $station->getFrontendType());
        $backend_changed = ($old_backend !== $station->getBackendType());
        $adapter_changed = $frontend_changed || $backend_changed;

        $hls_changed = $old_hls !== $station->getEnableHls();

        if ($frontend_changed) {
            $this->stationRepo->resetMounts($station);
        }

        if ($hls_changed || $backend_changed) {
            $this->stationRepo->resetHls($station);
        }

        if ($adapter_changed || !$station->getIsEnabled()) {
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

    protected function handleCreate(Entity\Station $station): Entity\Station
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

    protected function handleDelete(Entity\Station $station): void
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
