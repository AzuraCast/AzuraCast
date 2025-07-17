<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Error;
use App\Entity\Api\StationStreamer as ApiStationStreamer;
use App\Entity\Api\StationStreamerBroadcast as ApiStationStreamerBroadcast;
use App\Entity\Api\StationStreamerBroadcastRecording;
use App\Entity\Api\Status;
use App\Entity\Station;
use App\Entity\StationStreamer;
use App\Entity\StationStreamerBroadcast;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Utilities\File;
use App\Utilities\Time;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractApiCrudController<StationStreamerBroadcast>
 */
#[
    OA\Get(
        path: '/station/{station_id}/streamers/broadcasts',
        operationId: 'getStationAllBroadcasts',
        summary: 'List all broadcasts associated with the station.',
        tags: [OpenApi::TAG_STATIONS_STREAMERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: ApiStationStreamerBroadcast::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/streamer/{id}/broadcasts',
        operationId: 'getStationStreamerBroadcasts',
        summary: 'List all broadcasts associated with the specified streamer.',
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
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: ApiStationStreamerBroadcast::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/streamer/{id}/broadcast/{broadcast_id}/download',
        operationId: 'getStationStreamerDownloadBroadcast',
        summary: 'Download a single broadcast from a streamer.',
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
            new OA\Parameter(
                name: 'broadcast_id',
                description: 'Broadcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\SuccessWithDownload(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/streamer/{id}/broadcast/{broadcast_id}',
        operationId: 'getStationStreamerDeleteBroadcast',
        summary: 'Remove a single broadcast from a streamer.',
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
            new OA\Parameter(
                name: 'broadcast_id',
                description: 'Broadcast ID',
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
class BroadcastsController extends AbstractApiCrudController
{
    protected string $entityClass = StationStreamerBroadcast::class;

    public function __construct(
        protected readonly StationFilesystems $stationFilesystems,
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
        $id = Types::intOrNull($params['id'] ?? null);

        $station = $request->getStation();

        if (null !== $id) {
            $streamer = $this->getStreamer($station, $id);

            if (null === $streamer) {
                return $response->withStatus(404)
                    ->withJson(Error::notFound());
            }

            $query = $this->em->createQuery(
                <<<'DQL'
                    SELECT ssb
                    FROM App\Entity\StationStreamerBroadcast ssb
                    WHERE ssb.station = :station AND ssb.streamer = :streamer
                    ORDER BY ssb.timestampStart DESC
                DQL
            )->setParameter('station', $station)
                ->setParameter('streamer', $streamer);
        } else {
            $query = $this->em->createQuery(
                <<<'DQL'
                    SELECT ssb, ss
                    FROM App\Entity\StationStreamerBroadcast ssb
                    JOIN ssb.streamer ss
                    WHERE ssb.station = :station
                    ORDER BY ssb.timestampStart DESC
                DQL
            )->setParameter('station', $station);
        }

        $paginator = Paginator::fromQuery($query, $request);

        $router = $request->getRouter();
        $isInternal = $request->isInternal();
        $fsRecordings = $this->stationFilesystems->getRecordingsFilesystem($station);

        $paginator->setPostprocessor(
            function (StationStreamerBroadcast $row) use ($id, $router, $isInternal, $fsRecordings) {
                $return = new ApiStationStreamerBroadcast(
                    $row->id,
                    $row->timestampStart->format(Time::JS_ISO8601_FORMAT),
                    $row->timestampEnd?->format(Time::JS_ISO8601_FORMAT)
                );

                if (null === $id) {
                    $streamer = $row->streamer;
                    $return->streamer = new ApiStationStreamer(
                        $streamer->id,
                        $streamer->streamer_username,
                        $streamer->display_name
                    );
                }

                $routeParams = [
                    'broadcast_id' => $row->id,
                ];
                if (null === $id) {
                    $routeParams['id'] = $row->streamer->id;
                }

                $recordingPath = $row->recordingPath;

                if (!empty($recordingPath) && $fsRecordings->fileExists($recordingPath)) {
                    $return->recording = new StationStreamerBroadcastRecording(
                        $recordingPath,
                        $fsRecordings->fileSize($recordingPath),
                        $router->fromHere(
                            routeName: 'api:stations:streamer:broadcast:download',
                            routeParams: $routeParams,
                            absolute: !$isInternal
                        )
                    );
                }

                $return->links = [
                    'delete' => $router->fromHere(
                        routeName: 'api:stations:streamer:broadcast:delete',
                        routeParams: $routeParams,
                        absolute: !$isInternal
                    ),
                ];

                return $return;
            }
        );

        return $paginator->write($response);
    }

    public function downloadAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $broadcast = $this->getRecord($request, $params);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $recordingPath = $broadcast->recordingPath;

        if (empty($recordingPath)) {
            return $response->withStatus(400)
                ->withJson(new Error(400, __('No recording available.')));
        }

        $filename = basename($recordingPath);

        $fsRecordings = $this->stationFilesystems->getRecordingsFilesystem($station);

        return $response->streamFilesystemFile(
            $fsRecordings,
            $recordingPath,
            File::sanitizeFileName($broadcast->streamer->display_name) . '_' . $filename
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $broadcast = $this->getRecord($request, $params);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $recordingPath = $broadcast->recordingPath;

        if (!empty($recordingPath)) {
            $fsRecordings = $this->stationFilesystems->getRecordingsFilesystem($station);
            $fsRecordings->delete($recordingPath);
        }

        $this->em->remove($broadcast);
        $this->em->flush();

        return $response->withJson(Status::deleted());
    }

    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var StationStreamerBroadcast|null $broadcast */
        $broadcast = $this->em->getRepository(StationStreamerBroadcast::class)->findOneBy(
            [
                'id' => (int)$params['broadcast_id'],
                'station' => $request->getStation(),
            ]
        );
        return $broadcast;
    }

    protected function getStreamer(Station $station, int|string $id): ?StationStreamer
    {
        /** @var StationStreamer|null $streamer */
        $streamer = $this->em->getRepository(StationStreamer::class)->findOneBy(
            [
                'id' => (int)$id,
                'station' => $station,
            ]
        );
        return $streamer;
    }
}
