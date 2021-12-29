<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractStationApiCrudController<Entity\StationQueue> */
#[
    OA\Get(
        path: '/station/{station_id}/queue',
        operationId: 'getQueue',
        description: 'Return information about the upcoming song playback queue.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Queue'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_StationQueueDetailed')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Station not found'
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/queue/{id}',
        operationId: 'getQueueItem',
        description: 'Retrieve details of a single queued item.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Queue'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Queue Item ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_StationQueueDetailed')
            ),
            new OA\Response(
                response: 404,
                description: 'Station or Queue ID not found'
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/queue/{id}',
        operationId: 'deleteQueueItem',
        description: 'Delete a single queued item.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Queue'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Queue Item ID',
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
                response: 404,
                description: 'Station or Queue ID not found'
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    )
]
class QueueController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationQueue::class;
    protected string $resourceRouteName = 'api:stations:queue:record';

    public function __construct(
        protected Entity\ApiGenerator\StationQueueApiGenerator $queueApiGenerator,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        App\Doctrine\ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $query = $this->queueRepo->getUnplayedQuery($station);

        return $this->listPaginatedFromQuery(
            $request,
            $response,
            $query
        );
    }

    /**
     * @param object $record
     * @param ServerRequest $request
     */
    protected function viewRecord(object $record, ServerRequest $request): Entity\Api\StationQueueDetailed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $router = $request->getRouter();

        /** @var Entity\StationQueue $record */
        $row = ($this->queueApiGenerator)($record);
        $row->resolveUrls($router->getBaseUrl());

        $isInternal = ('true' === $request->getParam('internal', 'false'));

        $apiResponse = new Entity\Api\StationQueueDetailed();
        $apiResponse->fromParentObject($row);

        $apiResponse->sent_to_autodj = $record->getSentToAutodj();
        $apiResponse->is_played = $record->getIsPlayed();
        $apiResponse->autodj_custom_uri = $record->getAutodjCustomUri();
        $apiResponse->log = $record->getLog();

        $apiResponse->links = [
            'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], !$isInternal),
        ];

        return $apiResponse;
    }

    public function clearAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $this->queueRepo->clearUpcomingQueue($station);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
