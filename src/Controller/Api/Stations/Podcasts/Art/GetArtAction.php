<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

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
        string $podcast_id,
    ): ResponseInterface {
        $station = $request->getStation();

        // If a timestamp delimiter is added, strip it automatically.
        $podcast_id = explode('|', $podcast_id, 2)[0];

        $podcastPath = Entity\Podcast::getArtPath($podcast_id);

        $fsPodcasts = (new StationFilesystems($station))->getPodcastsFilesystem();

        if ($fsPodcasts->fileExists($podcastPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_YEAR)
                ->streamFilesystemFile($fsPodcasts, $podcastPath, null, 'inline');
        }

        return $response->withRedirect(
            (string)$stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
