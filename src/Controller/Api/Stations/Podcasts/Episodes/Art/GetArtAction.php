<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Art;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/art',
    description: 'Gets the album art for a podcast episode.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Podcasts'],
    parameters: [
        new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'podcast_id',
            description: 'Podcast ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
        new OA\Parameter(
            name: 'episode_id',
            description: 'Podcast Episode ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success'
        ),
        new OA\Response(
            response: 404,
            description: 'Record not found',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
        new OA\Response(
            response: 403,
            description: 'Access denied',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
    ]
)]
class GetArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        string $podcast_id,
        string $episode_id,
    ): ResponseInterface {
        $station = $request->getStation();

        // If a timestamp delimiter is added, strip it automatically.
        $episode_id = explode('|', $episode_id, 2)[0];

        $episodeArtPath = Entity\PodcastEpisode::getArtPath($episode_id);

        $fsPodcasts = (new StationFilesystems($station))->getPodcastsFilesystem();

        if ($fsPodcasts->fileExists($episodeArtPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_YEAR)
                ->streamFilesystemFile($fsPodcasts, $episodeArtPath, null, 'inline');
        }

        $podcastArtPath = Entity\Podcast::getArtPath($podcast_id);

        if ($fsPodcasts->fileExists($podcastArtPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_DAY)
                ->streamFilesystemFile($fsPodcasts, $podcastArtPath, null, 'inline');
        }

        return $response->withRedirect(
            (string)$stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
