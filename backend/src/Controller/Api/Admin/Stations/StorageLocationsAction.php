<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Stations;

use App\Controller\Api\Admin\StationsController;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Form\SimpleFormOptions;
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
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(
                        ref: SimpleFormOptions::class
                    )
                )
            ),
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
        $storageLocations = array_map(function ($locationType) {
            return SimpleFormOptions::fromArray(
                $this->storageLocationRepo->fetchSelectByType(
                    $locationType
                )
            );
        }, Station::getStorageLocationTypes());

        return $response->withJson($storageLocations);
    }
}
