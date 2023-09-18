<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/media',
    operationId: 'getPodcastEpisodeMedia',
    description: 'Gets the media for a podcast episode.',
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
final class GetMediaAction implements SingleActionInterface
{
    public function __construct(
        private readonly PodcastEpisodeRepository $episodeRepo,
        private readonly StationFilesystems $stationFilesystems,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(600);

        /** @var string $episodeId */
        $episodeId = $params['episode_id'];

        $station = $request->getStation();
        $episode = $this->episodeRepo->fetchEpisodeForStation($station, $episodeId);

        if ($episode instanceof PodcastEpisode) {
            $podcastMedia = $episode->getMedia();

            if ($podcastMedia instanceof PodcastMedia) {
                $fsPodcasts = $this->stationFilesystems->getPodcastsFilesystem($station);

                $path = $podcastMedia->getPath();

                if ($fsPodcasts->fileExists($path)) {
                    return $response->streamFilesystemFile(
                        $fsPodcasts,
                        $path,
                        $podcastMedia->getOriginalName()
                    );
                }
            }
        }

        return $response->withStatus(404)
            ->withJson(Error::notFound());
    }
}
