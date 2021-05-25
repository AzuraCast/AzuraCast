<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
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
                $fsStation = new StationFilesystems($station);
                $fsPodcasts = $fsStation->getPodcastsFilesystem();

                $path = $podcastMedia->getPath();

                if ($fsPodcasts->fileExists($path)) {
                    $fileMeta = $fsPodcasts->getMetadata($path);
                    $filename = $podcastMedia->getOriginalName() . '.' . $fileMeta['extension'];

                    return $response->streamFilesystemFile(
                        $fsPodcasts,
                        $path,
                        $filename
                    );
                }
            }
        }

        return $response->withStatus(404)
            ->withJson(new Entity\Api\Error(404, 'Media file not found.'));
    }
}
