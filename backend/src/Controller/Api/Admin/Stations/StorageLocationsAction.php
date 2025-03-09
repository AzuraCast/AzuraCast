<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Stations;

use App\Controller\Api\Admin\StationsController;
use App\Controller\SingleActionInterface;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/stations/storage-locations',
        operationId: 'getAdminStationStorageLocations',
        summary: 'List storage locations available for assignment to a station.',
        tags: [OpenApi::TAG_ADMIN_STATIONS],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class StorageLocationsAction extends StationsController implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $newStorageLocationMessage = __('Create a new storage location based on the base directory.');

        $storageLocations = array_map(function ($locationType) use ($newStorageLocationMessage) {
            return $this->storageLocationRepo->fetchSelectByType(
                $locationType,
                true,
                $newStorageLocationMessage
            );
        }, Station::getStorageLocationTypes());

        return $response->withJson($storageLocations);
    }
}
