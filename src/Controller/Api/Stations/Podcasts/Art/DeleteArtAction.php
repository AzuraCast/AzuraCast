<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/podcast/{podcast_id}/art',
    description: 'Removes the album art for a podcast.',
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
    ],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class DeleteArtAction
{
    public function __construct(
        private readonly Entity\Repository\PodcastRepository $podcastRepo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();

        $podcast = $this->podcastRepo->fetchPodcastForStation($station, $podcast_id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(
                    new Entity\Api\Error(
                        404,
                        __('Podcast not found!')
                    )
                );
        }

        $this->podcastRepo->removePodcastArt($podcast);
        $this->em->persist($podcast);
        $this->em->flush();

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
