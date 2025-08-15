<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Repository\StationScheduleRepository;
use App\Entity\Repository\StationStreamerRepository;
use App\Entity\Station;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\AutoDJ\Scheduler;
use App\Service\Flow\UploadedFile;
use App\Utilities\DateRange;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractScheduledEntityController<StationStreamer> */
#[
    OA\Get(
        path: '/station/{station_id}/streamers',
        operationId: 'getStreamers',
        summary: 'List all current Streamer/DJ accounts for the specified station.',
        tags: [OpenApi::TAG_STATIONS_STREAMERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationStreamer::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/streamers',
        operationId: 'addStreamer',
        summary: 'Create a new Streamer/DJ account.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationStreamer::class)
        ),
        tags: [OpenApi::TAG_STATIONS_STREAMERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationStreamer::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/streamer/{id}',
        operationId: 'getStreamer',
        summary: 'Retrieve details for a single Streamer/DJ account.',
        tags: [OpenApi::TAG_STATIONS_STREAMERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Streamer ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationStreamer::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/streamer/{id}',
        operationId: 'editStreamer',
        summary: 'Update details of a single Streamer/DJ account.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationStreamer::class)
        ),
        tags: [OpenApi::TAG_STATIONS_STREAMERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Streamer ID',
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
        path: '/station/{station_id}/streamer/{id}',
        operationId: 'deleteStreamer',
        summary: 'Delete a single Streamer/DJ account.',
        tags: [OpenApi::TAG_STATIONS_STREAMERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'StationStreamer ID',
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
final class StreamersController extends AbstractScheduledEntityController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = StationStreamer::class;
    protected string $resourceRouteName = 'api:stations:streamer';

    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
        StationScheduleRepository $scheduleRepo,
        Scheduler $scheduler,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($scheduleRepo, $scheduler, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(StationStreamer::class, 'e')
            ->where('e.station = :station')
            ->setParameter('station', $station);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'display_name' => 'e.display_name',
                'streamer_username' => 'e.streamer_username',
            ],
            'e.streamer_username'
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.streamer_username',
                'e.display_name',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();

        /** @var StationStreamer $record */
        $record = $this->editRecord(
            $data,
            new StationStreamer($station)
        );

        if (!empty($data['artwork_file'])) {
            $artwork = UploadedFile::fromArray($data['artwork_file'], $station->getRadioTempDir());
            $this->streamerRepo->writeArtwork(
                $record,
                $artwork->readAndDeleteUploadedFile()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        return $record;
    }

    public function scheduleAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $scheduleItems = $this->em->createQuery(
            <<<'DQL'
                SELECT ssc, sst
                FROM App\Entity\StationSchedule ssc
                LEFT JOIN ssc.streamer sst
                WHERE sst.station = :station AND sst.is_active = 1
            DQL
        )->setParameter('station', $station)
            ->execute();

        return $this->renderEvents(
            $request,
            $response,
            $scheduleItems,
            function (
                Station $station,
                StationSchedule $scheduleItem,
                DateRange $dateRange
            ) use (
                $request
            ) {
                /** @var StationStreamer $streamer */
                $streamer = $scheduleItem->streamer;

                return [
                    'id' => $streamer->id,
                    'title' => $streamer->display_name,
                    'start' => $dateRange->start->toIso8601String(),
                    'end' => $dateRange->end->toIso8601String(),
                    'edit_url' => $request->getRouter()->named(
                        'api:stations:streamer',
                        ['station_id' => $station->id, 'id' => $streamer->id]
                    ),
                ];
            }
        );
    }

    protected function viewRecord(object $record, ServerRequest $request): array
    {
        $return = parent::viewRecord($record, $request);

        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $return['has_custom_art'] = (0 !== $record->art_updated_at);

        $routeParams = [
            'id' => $record->id,
        ];
        if ($return['has_custom_art']) {
            $routeParams['timestamp'] = $record->art_updated_at;
        }

        $return['art'] = $router->fromHere(
            routeName: 'api:stations:streamer:art',
            routeParams: $routeParams,
            absolute: !$isInternal
        );

        $return['links']['broadcasts'] = $router->fromHere(
            routeName: 'api:stations:streamer:broadcasts',
            routeParams: ['id' => $record->id],
            absolute: !$isInternal
        );
        $return['links']['broadcasts_batch'] = $router->fromHere(
            routeName: 'api:stations:streamer:broadcasts:batch',
            routeParams: ['id' => $record->id],
            absolute: !$isInternal
        );

        $return['links']['art'] = $router->fromHere(
            routeName: 'api:stations:streamer:art-internal',
            routeParams: ['id' => $record->id],
            absolute: !$isInternal
        );

        return $return;
    }

    protected function deleteRecord(object $record): void
    {
        $this->streamerRepo->delete($record);
    }
}
