<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/media',
    description: 'Removes the media for a podcast episode.',
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
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class DeleteMediaAction
{
    public function __construct(
        private readonly Entity\Repository\PodcastEpisodeRepository $episodeRepo,
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
        $episode = $this->episodeRepo->fetchEpisodeForStation($station, $episode_id);

        if (!($episode instanceof Entity\PodcastEpisode)) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $podcastMedia = $episode->getMedia();

        if ($podcastMedia instanceof Entity\PodcastMedia) {
            $this->episodeRepo->deleteMedia($podcastMedia);
        }

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
