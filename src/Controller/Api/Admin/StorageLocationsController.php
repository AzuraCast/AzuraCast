<?php

namespace App\Controller\Api\Admin;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StorageLocationsController extends AbstractAdminApiCrudController
{
    protected string $entityClass = Entity\StorageLocation::class;
    protected string $resourceRouteName = 'api:admin:storage_location';

    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->storageLocationRepo = $storageLocationRepo;
    }

    /**
     * @OA\Get(path="/admin/storage_locations",
     *   tags={"Administration: Storage Locations"},
     *   description="List all current storage locations in the system.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StorageLocation"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/admin/storage_locations",
     *   tags={"Administration: Storage Locations"},
     *   description="Create a new storage location.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StorageLocation")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StorageLocation")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/admin/storage_location/{id}",
     *   tags={"Administration: Storage Locations"},
     *   description="Retrieve details for a single storage location.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StorageLocation")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/admin/storage_location/{id}",
     *   tags={"Administration: Storage Locations"},
     *   description="Update details of a single storage location.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StorageLocation")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Storage Location ID",
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
     * @OA\Delete(path="/admin/storage_location/{id}",
     *   tags={"Administration: Storage Locations"},
     *   description="Delete a single storage location.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Storage Location ID",
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

    protected function deleteRecord($record): void
    {
        if (!($record instanceof Entity\StorageLocation)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $stations = $this->storageLocationRepo->getStationsUsingLocation($record);

        if (0 !== count($stations)) {
            $stationNames = [];
            foreach ($stations as $station) {
                $stationNames[] = $station['name'];
            }

            throw new RuntimeException('This storage location has stations associated with it, and cannot be deleted until these stations are updated: '.implode(', ', $stationNames));
        }

        parent::deleteRecord($record);
    }
}
