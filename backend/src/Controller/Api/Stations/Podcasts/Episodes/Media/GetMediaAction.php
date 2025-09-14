<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Enums\PodcastSources;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\StationMedia;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/media',
    operationId: 'getPodcastEpisodeMedia',
    summary: 'Gets the media for a podcast episode.',
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
        new OpenApi\Response\SuccessWithDownload(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetMediaAction implements SingleActionInterface
{
    public function __construct(
        private PodcastEpisodeRepository $episodeRepo,
        private StationFilesystems $stationFilesystems,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(600);

        $episodeId = Types::string($params['episode_id'] ?? null);

        $station = $request->getStation();
        $podcast = $request->getPodcast();

        $episode = $this->episodeRepo->fetchEpisodeForPodcast(
            $podcast,
            $episodeId
        );

        if ($episode instanceof PodcastEpisode) {
            switch ($podcast->source) {
                case PodcastSources::Playlist:
                    $playlistMedia = $episode->playlist_media;

                    if ($playlistMedia instanceof StationMedia) {
                        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

                        set_time_limit(600);
                        return $response->streamFilesystemFile(
                            $fsMedia,
                            $playlistMedia->path
                        );
                    }
                    break;

                case PodcastSources::Manual:
                    $podcastMedia = $episode->media;

                    if ($podcastMedia instanceof PodcastMedia) {
                        $fsPodcasts = $this->stationFilesystems->getPodcastsFilesystem($station);

                        $path = $podcastMedia->path;

                        if ($fsPodcasts->fileExists($path)) {
                            return $response->streamFilesystemFile(
                                $fsPodcasts,
                                $path,
                                $podcastMedia->original_name
                            );
                        }
                    }
                    break;
            }
        }

        return $response->withStatus(404)
            ->withJson(Error::notFound());
    }
}
