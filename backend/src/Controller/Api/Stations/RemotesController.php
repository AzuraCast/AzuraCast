<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Api\StationRemote as ApiStationRemote;
use App\Entity\StationRemote;
use App\Exception\Http\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

/** @extends AbstractStationApiCrudController<StationRemote> */
#[
    OA\Get(
        path: '/station/{station_id}/remotes',
        operationId: 'getRelays',
        description: 'List all current remote relays.',
        tags: [OpenApi::TAG_STATIONS_REMOTE_RELAYS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: ApiStationRemote::class)
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/remotes',
        operationId: 'addRelay',
        description: 'Create a new remote relay.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiStationRemote::class)
        ),
        tags: [OpenApi::TAG_STATIONS_REMOTE_RELAYS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: ApiStationRemote::class)
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/remote/{id}',
        operationId: 'getRelay',
        description: 'Retrieve details for a single remote relay.',
        tags: [OpenApi::TAG_STATIONS_REMOTE_RELAYS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Remote Relay ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: ApiStationRemote::class)
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/remote/{id}',
        operationId: 'editRelay',
        description: 'Update details of a single remote relay.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiStationRemote::class)
        ),
        tags: [OpenApi::TAG_STATIONS_REMOTE_RELAYS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Remote Relay ID',
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
        path: '/station/{station_id}/remote/{id}',
        operationId: 'deleteRelay',
        description: 'Delete a single remote relay.',
        tags: [OpenApi::TAG_STATIONS_REMOTE_RELAYS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Remote Relay ID',
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
final class RemotesController extends AbstractStationApiCrudController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = StationRemote::class;
    protected string $resourceRouteName = 'api:stations:remote';

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(StationRemote::class, 'e')
            ->where('e.station = :station')
            ->setParameter('station', $station);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'display_name' => 'e.display_name',
                'enable_autodj' => 'e.enable_autodj',
            ],
            'e.display_name'
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.display_name',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): ApiStationRemote
    {
        $returnArray = $this->toArray($record);

        $return = ApiStationRemote::fromParent($returnArray);

        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $return->is_editable = $record->isEditable();

        $return->links = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
        ];

        return $return;
    }

    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        $record = parent::getRecord($request, $params);

        if ($record instanceof StationRemote && !$record->isEditable()) {
            throw PermissionDeniedException::create($request);
        }

        return $record;
    }
}
