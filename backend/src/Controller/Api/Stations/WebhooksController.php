<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\StationWebhook;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

/** @extends AbstractStationApiCrudController<StationWebhook> */
#[
    OA\Get(
        path: '/station/{station_id}/webhooks',
        operationId: 'getWebhooks',
        summary: 'List all current web hooks.',
        tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationWebhook::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/webhooks',
        operationId: 'addWebhook',
        summary: 'Create a new web hook.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationWebhook::class)
        ),
        tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationWebhook::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/webhook/{id}',
        operationId: 'getWebhook',
        summary: 'Retrieve details for a single web hook.',
        tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationWebhook::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/webhook/{id}',
        operationId: 'editWebhook',
        summary: 'Update details of a single web hook.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationWebhook::class)
        ),
        tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
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
        path: '/station/{station_id}/webhook/{id}',
        operationId: 'deleteWebhook',
        summary: 'Delete a single web hook.',
        tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
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
    OA\Post(
        path: '/station/{station_id}/webhook/{id}/clone',
        operationId: 'cloneWebhook',
        summary: 'Duplicate a single web hook.',
        tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Web Hook ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationWebhook::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class WebhooksController extends AbstractStationApiCrudController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = StationWebhook::class;
    protected string $resourceRouteName = 'api:stations:webhook';

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(StationWebhook::class, 'e')
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

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.name',
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
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'clone' => $router->fromHere(
                routeName: 'api:stations:webhook:clone',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'toggle' => $router->fromHere(
                routeName: 'api:stations:webhook:toggle',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'test' => $router->fromHere(
                routeName: 'api:stations:webhook:test',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
        ];

        return $return;
    }

    public function cloneAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $originalWebhook = $this->getRecord($request, $params);

        if (!$originalWebhook instanceof StationWebhook) {
            return $response->withStatus(404, 'Web hook not found.');
        }

        $this->em->detach($originalWebhook);

        $newWebhook = clone $originalWebhook;
        $newWebhook->name = $originalWebhook->name . ' (Copy)';
        $newWebhook->is_enabled = false;

        $this->em->persist($newWebhook);
        $this->em->flush();

        return $response->withJson(
            $this->viewRecord($newWebhook, $request),
            201
        );
    }
}
