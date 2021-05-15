<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Art;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\PodcastRepository $podcastRepo,
        string $episode_id,
    ): ResponseInterface {
        $station = $request->getStation();

        // If a timestamp delimiter is added, strip it automatically.
        $episode_id = explode('-', $episode_id)[0];

        $response = $response->withCacheLifetime(Response::CACHE_ONE_YEAR);
        $podcastPath = Entity\PodcastEpisode::getArtPath($episode_id);

        $fsStation = new StationFilesystems($station);
        $fsPodcasts = $fsStation->getPodcastsFilesystem();

        if ($fsPodcasts->fileExists($podcastPath)) {
            return $response->streamFilesystemFile($fsPodcasts, $podcastPath, null, 'inline');
        }

        return $response->withRedirect(
            (string)$stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
