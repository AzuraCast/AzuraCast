<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetMediaAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        string $episode_id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $episode = $episodeRepo->fetchEpisodeForStation($station, $episode_id);

        if ($episode instanceof Entity\PodcastEpisode) {
            $podcastMedia = $episode->getMedia();

            if ($podcastMedia instanceof Entity\PodcastMedia) {
                $fsPodcasts = (new StationFilesystems($station))->getPodcastsFilesystem();

                $path = $podcastMedia->getPath();

                if ($fsPodcasts->fileExists($path)) {
                    return $response->streamFilesystemFile(
                        $fsPodcasts,
                        $path,
                        $podcastMedia->getOriginalName()
                    );
                }
            }
        }

        return $response->withStatus(404)
            ->withJson(Entity\Api\Error::notFound());
    }
}
