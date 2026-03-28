<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/station/{station_id}/files/rename',
        operationId: 'postStationFilesRename',
        summary: 'Rename the specified files in the station media directory.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO: API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class RenameAction implements SingleActionInterface
{
    public function __construct(
        private BatchUtilities $batchUtilities,
        private StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $from = Types::string($request->getParam('file'));
        if (empty($from)) {
            return $response->withStatus(500)
                ->withJson(new Error(500, __('File not specified.')));
        }

        $to = Types::string($request->getParam('newPath'));
        if (empty($to)) {
            return $response->withStatus(500)
                ->withJson(new Error(500, __('New path not specified.')));
        }

        // No-op if paths match
        if ($from === $to) {
            return $response->withJson(Status::updated());
        }

        $station = $request->getStation();
        $storageLocation = $station->media_storage_location;

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);
        $fsMedia->move($from, $to);

        $this->batchUtilities->handleRename($from, $to, $storageLocation, $fsMedia);

        return $response->withJson(Status::updated());
    }
}
