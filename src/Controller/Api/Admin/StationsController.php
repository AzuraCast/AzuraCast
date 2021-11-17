<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Exception\ValidationException;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Configuration;
use App\Utilities\File;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractAdminApiCrudController<Entity\Station>
 */
class StationsController extends AbstractAdminApiCrudController
{
    protected string $entityClass = Entity\Station::class;
    protected string $resourceRouteName = 'api:admin:station';

    public function __construct(
        protected Entity\Repository\StationRepository $stationRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected Adapters $adapters,
        protected Configuration $configuration,
        protected ReloadableEntityManagerInterface $reloadableEm,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($reloadableEm, $serializer, $validator);
    }

    /**
     * @OA\Get(path="/admin/stations",
     *   tags={"Administration: Stations"},
     *   description="List all current stations in the system.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Station"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/admin/stations",
     *   tags={"Administration: Stations"},
     *   description="Create a new station.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/admin/station/{id}",
     *   tags={"Administration: Stations"},
     *   description="Retrieve details for a single station.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/admin/station/{id}",
     *   tags={"Administration: Stations"},
     *   description="Update details of a single station.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Station")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/admin/station/{id}",
     *   tags={"Administration: Stations"},
     *   description="Delete a single station.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links'] = [
            'self'   => (string)$router->fromHere(
                route_name: $this->resourceRouteName,
                route_params: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
            'manage' => (string)$router->named(
                route_name: 'stations:index:index',
                route_params: ['station_id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
            'clone'  => (string)$router->fromHere(
                route_name: 'api:admin:station:clone',
                route_params: ['id' => $record->getIdRequired()],
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

        foreach (Entity\Station::getStorageLocationTypes() as $storageLocationType => $locationKey) {
            $context[AbstractNormalizer::CALLBACKS][$locationKey] = fn(
                array $value
            ) => $value['id'];
        }

        return parent::toArray($record, $context);
    }

    protected function fromArray(array $data, ?object $record = null, array $context = []): object
    {
        foreach (Entity\Station::getStorageLocationTypes() as $locationKey) {
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

        // Get the original values to check for changes.
        $old_frontend = $original_record['frontend_type'];
        $old_backend = $original_record['backend_type'];

        $frontend_changed = ($old_frontend !== $station->getFrontendType());
        $backend_changed = ($old_backend !== $station->getBackendType());
        $adapter_changed = $frontend_changed || $backend_changed;

        if ($frontend_changed) {
            $frontend = $this->adapters->getFrontendAdapter($station);
            $this->stationRepo->resetMounts($station, $frontend);
        }

        if ($adapter_changed || !$station->getIsEnabled()) {
            $this->configuration->writeConfiguration($station, true);
        }

        return $station;
    }

    protected function handleCreate(Entity\Station $station): Entity\Station
    {
        $station->generateAdapterApiKey();

        $this->em->persist($station);
        $this->em->flush();

        $this->configuration->initializeConfiguration($station);

        // Create default mountpoints if station supports them.
        $frontend_adapter = $this->adapters->getFrontendAdapter($station);
        $this->stationRepo->resetMounts($station, $frontend_adapter);

        return $station;
    }

    protected function handleDelete(Entity\Station $station): void
    {
        $this->configuration->removeConfiguration($station);

        // Remove media folders.
        $radio_dir = $station->getRadioBaseDir();
        File::rmdirRecursive($radio_dir);

        // Save changes and continue to the last setup step.
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
