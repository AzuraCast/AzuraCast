<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\PodcastRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/podcast/{podcast_id}/art',
    operationId: 'deletePodcastArt',
    summary: 'Removes the album art for a podcast.',
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
    ],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class DeleteArtAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly PodcastRepository $podcastRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $podcast = $request->getPodcast();

        $this->podcastRepo->removePodcastArt($podcast);
        $this->em->persist($podcast);
        $this->em->flush();

        return $response->withJson(Status::deleted());
    }
}
