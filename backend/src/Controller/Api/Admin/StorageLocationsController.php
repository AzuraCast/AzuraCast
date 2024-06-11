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
use InvalidArgumentException;
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
        description: 'List all current storage locations in the system.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Storage Locations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_Admin_StorageLocation')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/admin/storage_locations',
        operationId: 'addStorageLocation',
        description: 'Create a new storage location.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Admin_StorageLocation')
        ),
        tags: ['Administration: Storage Locations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Admin_StorageLocation')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/admin/storage_location/{id}',
        operationId: 'getStorageLocation',
        description: 'Retrieve details for a single storage location.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Storage Locations'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Admin_StorageLocation')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/admin/storage_location/{id}',
        operationId: 'editStorageLocation',
        description: 'Update details of a single storage location.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Admin_StorageLocation')
        ),
        tags: ['Administration: Storage Locations'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/admin/storage_location/{id}',
        operationId: 'deleteStorageLocation',
        description: 'Delete a single storage location.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Storage Locations'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
    protected function viewRecord(object $record, ServerRequest $request): object
    {
        /** @var StorageLocation $record */
        $original = parent::viewRecord($record, $request);

        $return = new ApiStorageLocation();
        $return->fromParentObject($original);

        $return->storageQuotaBytes = (string)($record->getStorageQuotaBytes() ?? '');
        $return->storageUsedBytes = (string)$record->getStorageUsedBytes();
        $return->storageUsedPercent = $record->getStorageUsePercentage();
        $return->storageAvailable = $record->getStorageAvailable();
        $return->storageAvailableBytes = (string)($record->getStorageAvailableBytes() ?? '');
        $return->isFull = $record->isStorageFull();

        $return->uri = $record->getUri();

        $stationsRaw = $this->storageLocationRepo->getStationsUsingLocation($record);
        $stations = [];
        foreach ($stationsRaw as $station) {
            $stations[] = $station->getName();
        }
        $return->stations = $stations;

        return $return;
    }

    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof StorageLocation)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $stations = $this->storageLocationRepo->getStationsUsingLocation($record);

        if (0 !== count($stations)) {
            $stationNames = [];
            foreach ($stations as $station) {
                $stationNames[] = $station->getName();
            }

            throw new RuntimeException(
                'This storage location has stations associated with it, and cannot be '
                . ' deleted until these stations are updated: ' . implode(', ', $stationNames)
            );
        }

        parent::deleteRecord($record);
    }
}
