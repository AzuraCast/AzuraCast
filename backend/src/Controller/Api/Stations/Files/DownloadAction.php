<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/files/download',
    operationId: 'getStationFileDownload',
    summary: 'Download a file by relative path.',
    tags: [OpenApi::TAG_STATIONS_MEDIA],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'file',
            description: 'The relative path of the file in the Media filesystem.',
            in: 'query',
            schema: new OA\Schema(type: 'string'),
        ),
    ],
    responses: [
        new OpenApi\Response\SuccessWithDownload(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class DownloadAction implements SingleActionInterface
{
    public function __construct(
        private StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $path = Types::string($request->getParam('file'));

        if (!$fsMedia->fileExists($path)) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        return $response->streamFilesystemFile($fsMedia, $path);
    }
}
