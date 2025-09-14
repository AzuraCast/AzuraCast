<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes;

use App\Controller\SingleActionInterface;
use App\Entity\Api\PodcastEpisode as ApiPodcastEpisode;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/public/podcast/{podcast_id}/episode/{episode_id}',
    operationId: 'getStationPublicPodcastEpisode',
    summary: 'Get information for a public episode of a public podcast.',
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
        new OA\Parameter(
            name: 'episode_id',
            description: 'Podcast Episode ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: ApiPodcastEpisode::class
            )
        ),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetEpisodeAction implements SingleActionInterface
{
    public function __construct(
        private PodcastEpisodeRepository $episodeRepo,
        private PodcastEpisodeApiGenerator $episodeApiGen
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $episodeId = Types::string($params['episode_id'] ?? null);

        $episode = $this->episodeRepo->fetchEpisodeForPodcast(
            $request->getPodcast(),
            $episodeId
        );

        if (null === $episode) {
            throw NotFoundException::podcast();
        }

        return $response->withJson(
            $this->episodeApiGen->__invoke($episode, $request)
        );
    }
}
