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
        string $podcast_id,
        string $episode_id,
    ): ResponseInterface {
        $station = $request->getStation();

        // If a timestamp delimiter is added, strip it automatically.
        $episode_id = explode('|', $episode_id, 2)[0];

        $episodeArtPath = Entity\PodcastEpisode::getArtPath($episode_id);

        $fsPodcasts = (new StationFilesystems($station))->getPodcastsFilesystem();

        if ($fsPodcasts->fileExists($episodeArtPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_YEAR)
                ->streamFilesystemFile($fsPodcasts, $episodeArtPath, null, 'inline');
        }

        $podcastArtPath = Entity\Podcast::getArtPath($podcast_id);

        if ($fsPodcasts->fileExists($podcastArtPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_DAY)
                ->streamFilesystemFile($fsPodcasts, $podcastArtPath, null, 'inline');
        }

        return $response->withRedirect(
            (string)$stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
