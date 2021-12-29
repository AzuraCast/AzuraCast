<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSortResults;
use App\Entity;
use App\Exception\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

/** @extends AbstractStationApiCrudController<Entity\StationRemote> */
#[
    OA\Get(
        path: '/station/{station_id}/remotes',
        operationId: 'getRelays',
        description: 'List all current remote relays.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Remote Relays'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_StationRemote')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/remotes',
        operationId: 'addRelay',
        description: 'Create a new remote relay.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_StationRemote')
        ),
        tags: ['Stations: Remote Relays'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_StationRemote')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/remote/{id}',
        operationId: 'getRelay',
        description: 'Retrieve details for a single remote relay.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Remote Relays'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
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
                content: new OA\JsonContent(ref: '#/components/schemas/Api_StationRemote')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/remote/{id}',
        operationId: 'editRelay',
        description: 'Update details of a single remote relay.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_StationRemote')
        ),
        tags: ['Stations: Remote Relays'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
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
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/remote/{id}',
        operationId: 'deleteRelay',
        description: 'Delete a single remote relay.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Remote Relays'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
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
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    )
]
class RemotesController extends AbstractStationApiCrudController
{
    use CanSortResults;

    protected string $entityClass = Entity\StationRemote::class;
    protected string $resourceRouteName = 'api:stations:remote';

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Entity\StationRemote::class, 'e')
            ->where('e.station = :station')
            ->setParameter('station', $station);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'display_name'  => 'e.display_name',
                'enable_autodj' => 'e.enable_autodj',
            ],
            'e.display_name'
        );

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('(e.display_name LIKE :name)')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof Entity\StationRemote)) {
            throw new InvalidArgumentException(
                sprintf('Record must be an instance of %s.', Entity\StationRemote::class)
            );
        }

        $returnArray = $this->toArray($record);

        $return = new Entity\Api\StationRemote();
        $return->fromParentObject($returnArray);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return->is_editable = $record->isEditable();

        $return->links = [
            'self' => (string)$router->fromHere(
                route_name:   $this->resourceRouteName,
                route_params: ['id' => $record->getIdRequired()],
                absolute:     !$isInternal
            ),
        ];

        return $return;
    }

    /**
     * @inheritDoc
     */
    protected function getRecord(Entity\Station $station, int|string $id): ?object
    {
        $record = parent::getRecord($station, $id);

        if ($record instanceof Entity\StationRemote && !$record->isEditable()) {
            throw new PermissionDeniedException('This record cannot be edited.');
        }

        return $record;
    }
}
