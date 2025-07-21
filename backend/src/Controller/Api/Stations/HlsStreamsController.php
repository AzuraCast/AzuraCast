<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\StationHlsStream;
use App\Exception\ValidationException;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractStationApiCrudController<StationHlsStream> */
#[
    OA\Get(
        path: '/station/{station_id}/hls_streams',
        operationId: 'getHlsStreams',
        summary: 'List all current HLS streams.',
        tags: [OpenApi::TAG_STATIONS_HLS_STREAMS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationHlsStream::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/hls_streams',
        operationId: 'addHlsStream',
        summary: 'Create a new HLS stream.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationHlsStream::class)
        ),
        tags: [OpenApi::TAG_STATIONS_HLS_STREAMS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationHlsStream::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/hls_stream/{id}',
        operationId: 'getHlsStream',
        summary: 'Retrieve details for a single HLS stream.',
        tags: [OpenApi::TAG_STATIONS_HLS_STREAMS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'HLS Stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationHlsStream::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/hls_stream/{id}',
        operationId: 'editHlsStream',
        summary: 'Update details of a single HLS stream.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationHlsStream::class)
        ),
        tags: [OpenApi::TAG_STATIONS_HLS_STREAMS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'HLS Stream ID',
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
        path: '/station/{station_id}/hls_stream/{id}',
        operationId: 'deleteHlsStream',
        summary: 'Delete a single HLS stream.',
        tags: [OpenApi::TAG_STATIONS_HLS_STREAMS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'HLS Stream ID',
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
final class HlsStreamsController extends AbstractStationApiCrudController
{
    protected string $entityClass = StationHlsStream::class;
    protected string $resourceRouteName = 'api:stations:hls_stream';

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();
        if ($station->max_hls_streams !== 0 && $station->max_hls_streams <= $station->hls_streams->count()) {
            throw new ValidationException(
                __('Unable to create a new stream, station\'s maximum HLS streams reached.')
            );
        }

        return parent::createRecord($request, $data);
    }
}
