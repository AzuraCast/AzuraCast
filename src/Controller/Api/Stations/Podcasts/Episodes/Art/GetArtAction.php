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
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
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
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class GetArtAction
{
    public function __construct(
        private readonly Entity\Repository\StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $podcast_id,
        string $episode_id
    ): ResponseInterface {
        $station = $request->getStation();

        // If a timestamp delimiter is added, strip it automatically.
        $episode_id = explode('|', $episode_id, 2)[0];

        $episodeArtPath = Entity\PodcastEpisode::getArtPath($episode_id);

        $fsPodcasts = (new StationFilesystems($station))->getPodcastsFilesystem();

        if ($fsPodcasts->fileExists($episodeArtPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_YEAR)
                ->streamFilesystemFile($fsPodcasts, $episodeArtPath, null, 'inline', false);
        }

        $podcastArtPath = Entity\Podcast::getArtPath($podcast_id);

        if ($fsPodcasts->fileExists($podcastArtPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_DAY)
                ->streamFilesystemFile($fsPodcasts, $podcastArtPath, null, 'inline', false);
        }

        return $response->withRedirect(
            (string)$this->stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
