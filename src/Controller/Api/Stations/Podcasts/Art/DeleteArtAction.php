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
        new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'podcast_id',
            description: 'Podcast ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
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
class DeleteArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastRepository $podcastRepo,
        EntityManagerInterface $em,
        string $podcast_id,
    ): ResponseInterface {
        $station = $request->getStation();

        $podcast = $podcastRepo->fetchPodcastForStation($station, $podcast_id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(
                    new Entity\Api\Error(
                        404,
                        __('Podcast not found!')
                    )
                );
        }

        $podcastRepo->removePodcastArt($podcast);
        $em->persist($podcast);
        $em->flush();

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
