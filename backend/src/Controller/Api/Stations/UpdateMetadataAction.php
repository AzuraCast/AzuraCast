<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Adapters;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Post(
        path: '/station/{station_id}/nowplaying/update',
        operationId: 'postStationNowPlayingUpdate',
        summary: 'Manually update the Now Playing metadata for the station.',
        tags: [OpenApi::TAG_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class UpdateMetadataAction implements SingleActionInterface
{
    public function __construct(
        private Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $this->adapters->requireBackendAdapter($station);

        $output = $backend->updateMetadata($station, $request->getParams());

        return $response->withJson(
            new Status(true, 'Metadata updated successfully: ' . implode(', ', $output))
        );
    }
}
