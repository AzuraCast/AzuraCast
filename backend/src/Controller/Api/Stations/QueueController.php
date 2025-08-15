<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Cache\QueueLogCache;
use App\Entity\Api\StationQueueDetailed;
use App\Entity\Api\Status;
use App\Entity\ApiGenerator\StationQueueApiGenerator;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\StationQueue;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractStationApiCrudController<StationQueue> */
#[
    OA\Get(
        path: '/station/{station_id}/queue',
        operationId: 'getQueue',
        summary: 'Return information about the upcoming song playback queue.',
        tags: [OpenApi::TAG_STATIONS_QUEUE],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: StationQueue::class),
                            new OA\Schema(ref: StationQueueDetailed::class),
                        ]
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/queue/{id}',
        operationId: 'getQueueItem',
        summary: 'Retrieve details of a single queued item.',
        tags: [OpenApi::TAG_STATIONS_QUEUE],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Queue Item ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: StationQueue::class),
                        new OA\Schema(ref: StationQueueDetailed::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/queue/{id}',
        operationId: 'deleteQueueItem',
        summary: 'Delete a single queued item.',
        tags: [OpenApi::TAG_STATIONS_QUEUE],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Queue Item ID',
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
final class QueueController extends AbstractStationApiCrudController
{
    protected string $entityClass = StationQueue::class;
    protected string $resourceRouteName = 'api:stations:queue:record';

    public function __construct(
        private readonly StationQueueApiGenerator $queueApiGenerator,
        private readonly StationQueueRepository $queueRepo,
        private readonly QueueLogCache $queueLogCache,
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
        $station = $request->getStation();
        $qb = $this->queueRepo->getUnplayedBaseQuery($station);

        $searchPhrase = Types::stringOrNull($request->getQueryParam('searchPhrase'), true);
        if (null !== $searchPhrase) {
            $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.text LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery(
            $request,
            $response,
            $qb->getQuery()
        );
    }

    protected function viewRecord(object $record, ServerRequest $request): array
    {
        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $row = $this->queueApiGenerator->__invoke($record);

        $apiResponse = new StationQueueDetailed();
        $apiResponse->sent_to_autodj = $record->sent_to_autodj;
        $apiResponse->is_played = $record->is_played;
        $apiResponse->autodj_custom_uri = $record->autodj_custom_uri;
        $apiResponse->log = $this->queueLogCache->getLog($record);

        $apiResponse->links = [
            'self' => $router->fromHere(
                $this->resourceRouteName,
                ['id' => $record->id],
                [],
                !$isInternal
            ),
        ];

        return [
            ...get_object_vars($row),
            ...get_object_vars($apiResponse),
        ];
    }

    public function clearAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $this->queueRepo->clearUpcomingQueue($station);

        return $response->withJson(Status::deleted());
    }
}
