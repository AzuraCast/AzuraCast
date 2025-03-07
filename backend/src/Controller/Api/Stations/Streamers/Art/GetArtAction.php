<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Entity\StationStreamer;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/streamer/{id}/art',
    operationId: 'getStreamerArt',
    description: 'Gets the default album art for a streamer.',
    security: [],
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
        new OpenApi\Response\SuccessWithImage(),
        new OpenApi\Response\Redirect(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class GetArtAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $artworkPath = StationStreamer::getArtworkPath($id);

        $fsConfig = StationFilesystems::buildConfigFilesystem($station);
        if ($fsConfig->fileExists($artworkPath)) {
            return $response->streamFilesystemFile($fsConfig, $artworkPath, null, 'inline', false);
        }

        return $response->withRedirect(
            (string)$this->stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
