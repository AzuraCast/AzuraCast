<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Art;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/art',
    operationId: 'deletePodcastEpisodeArt',
    summary: 'Removes the album art for a podcast episode.',
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
        new OpenApi\Response\Success(
            content: new OA\JsonContent(ref: Status::class)
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class DeleteArtAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly PodcastEpisodeRepository $episodeRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $episodeId = Types::string($params['episode_id'] ?? null);

        $episode = $this->episodeRepo->fetchEpisodeForPodcast(
            $request->getPodcast(),
            $episodeId
        );

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $this->episodeRepo->removeEpisodeArt($episode);
        $this->em->persist($episode);
        $this->em->flush();

        return $response->withJson(Status::deleted());
    }
}
