<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\Enums\PodcastSources;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/media',
    operationId: 'deletePodcastEpisodeMedia',
    summary: 'Removes the media for a podcast episode.',
    tags: [OpenApi::TAG_STATIONS_PODCASTS],
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
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class DeleteMediaAction implements SingleActionInterface
{
    public function __construct(
        private PodcastEpisodeRepository $episodeRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $podcast = $request->getPodcast();

        if ($podcast->source !== PodcastSources::Manual) {
            throw new InvalidArgumentException('Media cannot be manually set on this podcast.');
        }

        $episodeId = Types::string($params['episode_id'] ?? null);
        $episode = $this->episodeRepo->fetchEpisodeForPodcast($podcast, $episodeId);

        if (!($episode instanceof PodcastEpisode)) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $podcastMedia = $episode->media;
        if ($podcastMedia instanceof PodcastMedia) {
            $this->episodeRepo->deleteMedia($podcastMedia);
        }

        return $response->withJson(Status::deleted());
    }
}
