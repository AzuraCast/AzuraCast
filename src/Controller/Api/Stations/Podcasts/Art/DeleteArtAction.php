<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
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
        /** @var string $podcastId */
        $podcastId = $params['podcast_id'];

        $station = $request->getStation();

        $podcast = $this->podcastRepo->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(
                    new Error(
                        404,
                        __('Podcast not found!')
                    )
                );
        }

        $this->podcastRepo->removePodcastArt($podcast);
        $this->em->persist($podcast);
        $this->em->flush();

        return $response->withJson(Status::deleted());
    }
}
