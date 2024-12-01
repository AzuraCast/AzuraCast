<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Nginx\Nginx;
use App\OpenApi;
use App\Radio\Configuration;
use App\Utilities\File;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
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
    use CanSearchResults;

    protected string $entityClass = Station::class;
    protected string $resourceRouteName = 'api:admin:station';

    public function __construct(
        protected StationRepository $stationRepo,
        protected StorageLocationRepository $storageLocationRepo,
        protected StationQueueRepository $queueRepo,
        protected Configuration $configuration,
        protected Nginx $nginx,
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

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.name',
                'e.short_name',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        $return = $this->toArray($record);

        $isInternal = $request->isInternal();
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

        $rewriteConfiguration = false;

        // Detect a change in the station's base config directory.
        if (
            !empty($originalRecord['radio_base_dir'])
            && $originalRecord['radio_base_dir'] !== $station->getRadioBaseDir()
        ) {
            $rewriteConfiguration = true;
            $this->handleBaseDirRename($station, $originalRecord['radio_base_dir']);
        }

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

        // Check for changes in essential variables.
        if ($originalRecord['short_name'] !== $station->getShortName()) {
            $rewriteConfiguration = true;
            $this->nginx->writeConfiguration($station);
        }

        $frontendChanged = ($originalRecord['frontend_type'] !== $station->getFrontendType());
        if ($frontendChanged) {
            $rewriteConfiguration = true;
            $this->stationRepo->resetMounts($station);
        }

        $backendChanged = ($originalRecord['backend_type'] !== $station->getBackendType());
        $hlsChanged = (bool)$originalRecord['enable_hls'] !== $station->getEnableHls();
        if ($backendChanged || $hlsChanged) {
            $rewriteConfiguration = true;
            $this->stationRepo->resetHls($station);
        }

        if ((bool)$originalRecord['is_enabled'] !== $station->getIsEnabled()) {
            $rewriteConfiguration = true;
        }

        // Apply "Max Bitrate"
        $oldMaxBitrate = (int)$originalRecord['max_bitrate'];

        if (
            ($oldMaxBitrate !== 0 && $station->getMaxBitrate() !== 0 && $oldMaxBitrate > $station->getMaxBitrate())
            || ($oldMaxBitrate === 0 && $station->getMaxBitrate() !== 0)
        ) {
            if (!$frontendChanged) {
                $this->stationRepo->reduceMountsBitrateToLimit($station);
            }

            if (!$hlsChanged && !$backendChanged) {
                $this->stationRepo->reduceHlsBitrateToLimit($station);
            }

            $this->stationRepo->reduceRemoteRelayAutoDjBitrateToLimit($station);
            $this->stationRepo->reduceLiveBroadcastRecordingBitrateToLimit($station);
        }

        // Apply "Max Mount Points"
        $oldMaxMounts = (int)$originalRecord['max_mounts'];

        if (
            $station->getMaxMounts() !== 0
            && ($oldMaxMounts > $station->getMaxMounts() || $oldMaxMounts === 0)
        ) {
            $rewriteConfiguration = true;
            $this->stationRepo->reduceMountPointsToLimit($station);
        }

        // Apply "Max HLS Streams"
        $oldMaxHlsStreams = (int)$originalRecord['max_hls_streams'];

        if (
            $station->getMaxHlsStreams() !== 0
            && ($oldMaxHlsStreams > $station->getMaxHlsStreams() || $oldMaxHlsStreams === 0)
        ) {
            $rewriteConfiguration = true;
            $this->stationRepo->reduceHlsStreamsToLimit($station);
        }

        if ($rewriteConfiguration) {
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

    protected function handleBaseDirRename(
        Station $station,
        string $originalPath
    ): void {
        $newPath = $station->getRadioBaseDir();

        // Unlink the old path's supervisor config file.
        $originalConfPath = Configuration::getSupervisorConfPath($originalPath);
        @unlink($originalConfPath);

        // Force a reload of supervisor services and stop all for this station.
        $this->configuration->removeConfiguration($station);

        // Move any local storage locations that only point to this station.
        $allStorageLocationsMoved = true;

        foreach ($station->getAllStorageLocations() as $storageLocation) {
            if (StorageLocationAdapters::Local !== $storageLocation->getAdapter()) {
                continue;
            }

            $stationsUsingLocation = $this->storageLocationRepo->getStationsUsingLocation($storageLocation);
            if (count($stationsUsingLocation) > 1) {
                $allStorageLocationsMoved = false;
                continue;
            }

            $locationPath = $storageLocation->getPath();

            if (Path::isBasePath($originalPath, $locationPath)) {
                $newLocationPath = Path::makeAbsolute(
                    Path::makeRelative($locationPath, $originalPath),
                    $newPath
                );

                $storageLocation->setPath($newLocationPath);
                $this->em->persist($storageLocation);

                File::moveDirectoryContents(
                    $locationPath,
                    $newLocationPath
                );
            }
        }

        // Move non-storage-location directories.
        foreach (Station::NON_STORAGE_LOCATION_DIRS as $otherDir) {
            $dirOldPath = $originalPath . '/' . $otherDir;
            $dirNewPath = $newPath . '/' . $otherDir;

            File::moveDirectoryContents(
                $dirOldPath,
                $dirNewPath
            );
        }

        // Clear the old directory entirely if all storage locations are moved.
        if ($allStorageLocationsMoved) {
            (new Filesystem())->remove($originalPath);
        }
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
        $fsUtils = new Filesystem();
        $stationBaseDir = $station->getRadioBaseDir();
        foreach (Station::NON_STORAGE_LOCATION_DIRS as $otherDir) {
            $fsUtils->remove($stationBaseDir . '/' . $otherDir);
        }

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
