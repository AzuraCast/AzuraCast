<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetMediaArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        string $podcast_media_id,
    ): ResponseInterface {
        $station = $request->getStation();

        // If a timestamp delimiter is added, strip it automatically.
        $podcast_media_id = explode('|', $podcast_media_id)[0];

        $fsStation = new StationFilesystems($station);
        $fsPodcasts = $fsStation->getPodcastsFilesystem();

        $artPath = Entity\PodcastMedia::getArtPath($podcast_media_id);
        if ($fsPodcasts->fileExists($artPath)) {
            return $response
                ->withCacheLifetime(Response::CACHE_ONE_YEAR)
                ->streamFilesystemFile($fsPodcasts, $artPath, null, 'inline');
        }

        return $response->withRedirect(
            (string)$stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
