<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\Enums\PodcastSources;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use App\Utilities\Types;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/media',
    operationId: 'postPodcastEpisodeMedia',
    summary: 'Sets the media for a podcast episode.',
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
final readonly class PostMediaAction implements SingleActionInterface
{
    public function __construct(
        private PodcastEpisodeRepository $episodeRepo,
        private StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $podcast = $request->getPodcast();
        $station = $request->getStation();

        if ($podcast->source !== PodcastSources::Manual) {
            throw new InvalidArgumentException('Media cannot be manually set on this podcast.');
        }

        $episodeId = Types::stringOrNull($params['episode_id'] ?? null, true);

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $episodeId) {
            $episode = $this->episodeRepo->fetchEpisodeForPodcast($podcast, $episodeId);

            if (null === $episode) {
                return $response->withStatus(404)
                    ->withJson(Error::notFound());
            }

            $this->episodeRepo->uploadMedia(
                $episode,
                $flowResponse->getClientFilename(),
                $flowResponse->getUploadedPath(),
                $this->stationFilesystems->getPodcastsFilesystem($station)
            );

            return $response->withJson(Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
