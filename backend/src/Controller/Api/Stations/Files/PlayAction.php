<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationMediaRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/file/{id}/play',
        operationId: 'getPlayFile',
        summary: 'Download or play a given file by ID.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'media_id',
                description: 'Media ID',
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
    )
]
#[
    OA\Get(
        path: '/station/{station_id}/file/{song_id}/play',
        operationId: 'getPlayFileSongId',
        summary: 'Download or play a given file by song_id.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'song_id',
                description: 'Media ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'guid')
            ),
        ],
        responses: [
            new OpenApi\Response\SuccessWithDownload(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class PlayAction implements SingleActionInterface
{
    public function __construct(
        private StationMediaRepository $mediaRepo,
        private StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(600);

        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $media = $this->mediaRepo->requireForStation($id, $station);

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        return $response->streamFilesystemFile($fsMedia, $media->path);
    }
}
