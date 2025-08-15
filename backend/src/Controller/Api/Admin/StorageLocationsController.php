<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Admin\StorageLocation as ApiStorageLocation;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\StorageLocation;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<StorageLocation> */
#[
    OA\Get(
        path: '/admin/storage_locations',
        operationId: 'getStorageLocations',
        summary: 'List all current storage locations in the system.',
        tags: [OpenApi::TAG_ADMIN_STORAGE_LOCATIONS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: StorageLocation::class),
                            new OA\Schema(ref: ApiStorageLocation::class),
                        ]
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/admin/storage_locations',
        operationId: 'addStorageLocation',
        summary: 'Create a new storage location.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiStorageLocation::class)
        ),
        tags: [OpenApi::TAG_ADMIN_STORAGE_LOCATIONS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: StorageLocation::class),
                        new OA\Schema(ref: ApiStorageLocation::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/storage_location/{id}',
        operationId: 'getStorageLocation',
        summary: 'Retrieve details for a single storage location.',
        tags: [OpenApi::TAG_ADMIN_STORAGE_LOCATIONS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: StorageLocation::class),
                        new OA\Schema(ref: ApiStorageLocation::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/admin/storage_location/{id}',
        operationId: 'editStorageLocation',
        summary: 'Update details of a single storage location.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StorageLocation::class)
        ),
        tags: [OpenApi::TAG_ADMIN_STORAGE_LOCATIONS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Storage Location ID',
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
        path: '/admin/storage_location/{id}',
        operationId: 'deleteStorageLocation',
        summary: 'Delete a single storage location.',
        tags: [OpenApi::TAG_ADMIN_STORAGE_LOCATIONS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Storage Location ID',
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
final class StorageLocationsController extends AbstractApiCrudController
{
    protected string $entityClass = StorageLocation::class;
    protected string $resourceRouteName = 'api:admin:storage_location';

    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo,
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
        $qb = $this->em->createQueryBuilder();

        $qb->select('sl')
            ->from(StorageLocation::class, 'sl');

        $type = $request->getQueryParam('type');
        if (!empty($type)) {
            $qb->andWhere('sl.type = :type')
                ->setParameter('type', $type);
        }

        $query = $qb->getQuery();

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    /** @inheritDoc */
    protected function viewRecord(object $record, ServerRequest $request): array
    {
        $original = parent::viewRecord($record, $request);

        $return = new ApiStorageLocation();
        $return->storageUsedPercent = $record->getStorageUsePercentage();
        $return->isFull = $record->isStorageFull();
        $return->uri = $record->getUri();

        $stationsRaw = $this->storageLocationRepo->getStationsUsingLocation($record);
        $stations = [];
        foreach ($stationsRaw as $station) {
            $stations[] = $station->name;
        }
        $return->stations = $stations;
        $return->links = $original['links'];

        return [
            ...$original,
            ...get_object_vars($return),
        ];
    }

    protected function deleteRecord(object $record): void
    {
        $stations = $this->storageLocationRepo->getStationsUsingLocation($record);

        if (0 !== count($stations)) {
            $stationNames = [];
            foreach ($stations as $station) {
                $stationNames[] = $station->name;
            }

            throw new RuntimeException(
                'This storage location has stations associated with it, and cannot be '
                . ' deleted until these stations are updated: ' . implode(', ', $stationNames)
            );
        }

        parent::deleteRecord($record);
    }
}
