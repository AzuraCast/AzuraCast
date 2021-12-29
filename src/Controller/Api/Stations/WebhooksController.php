<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSortResults;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

/** @extends AbstractStationApiCrudController<Entity\StationWebhook> */
#[
    OA\Get(
        path: '/station/{station_id}/webhooks',
        operationId: 'getWebhooks',
        description: 'List all current web hooks.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Web Hooks'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/StationWebhook')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/webhooks',
        operationId: 'addWebhook',
        description: 'Create a new web hook.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationWebhook')
        ),
        tags: ['Stations: Web Hooks'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationWebhook')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/webhook/{id}',
        operationId: 'getWebhook',
        description: 'Retrieve details for a single web hook.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Web Hooks'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationWebhook')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/webhook/{id}',
        operationId: 'editWebhook',
        description: 'Update details of a single web hook.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationWebhook')
        ),
        tags: ['Stations: Web Hooks'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
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
        path: '/station/{station_id}/webhook/{id}',
        operationId: 'deleteWebhook',
        description: 'Delete a single web hook relay.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Web Hooks'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
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
class WebhooksController extends AbstractStationApiCrudController
{
    use CanSortResults;

    protected string $entityClass = Entity\StationWebhook::class;
    protected string $resourceRouteName = 'api:stations:webhook';

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Entity\StationWebhook::class, 'e')
            ->where('e.station = :station')
            ->setParameter('station', $station);

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
            $qb->andWhere('(e.name LIKE :name)')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof Entity\StationWebhook)) {
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
            'toggle' => (string)$router->fromHere(
                route_name: 'api:stations:webhook:toggle',
                route_params: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
            'test'   => (string)$router->fromHere(
                route_name: 'api:stations:webhook:test',
                route_params: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
        ];

        return $return;
    }
}
