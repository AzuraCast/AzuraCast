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
use App\Service\Flow;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/art',
    operationId: 'postPodcastEpisodeArt',
    summary: 'Sets the album art for a podcast episode.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
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
final class PostArtAction implements SingleActionInterface
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
        $episodeId = Types::stringOrNull($params['episode_id'] ?? null, true);

        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $episodeId) {
            $episode = $this->episodeRepo->fetchEpisodeForPodcast(
                $request->getPodcast(),
                $episodeId
            );

            if (null === $episode) {
                return $response->withStatus(404)
                    ->withJson(Error::notFound());
            }

            $this->episodeRepo->writeEpisodeArt(
                $episode,
                $flowResponse->readAndDeleteUploadedFile()
            );

            $this->em->flush();

            return $response->withJson(Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
