<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

use App\Controller\SingleActionInterface;
use App\Customization;
use App\Entity\Podcast;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/podcast/{podcast_id}/art',
    operationId: 'getPodcastArt',
    summary: 'Gets the album art for a podcast.',
    security: [],
    tags: [OpenApi::TAG_PUBLIC_STATIONS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'podcast_id',
            description: 'Podcast ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
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
final readonly class GetArtAction implements SingleActionInterface
{
    public function __construct(
        private Customization $customization,
        private StationFilesystems $stationFilesystems,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $podcast = $request->getPodcast();
        $station = $request->getStation();

        $podcastPath = Podcast::getArtPath($podcast->id);

        $fsPodcasts = $this->stationFilesystems->getPodcastsFilesystem($station);

        if ($fsPodcasts->fileExists($podcastPath)) {
            return $response->streamFilesystemFile($fsPodcasts, $podcastPath, null, 'inline', false);
        }

        return $response->withRedirect(
            (string)$this->customization->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
