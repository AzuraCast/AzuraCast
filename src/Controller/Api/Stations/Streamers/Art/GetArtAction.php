<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetArtAction
{
    public function __construct(
        private readonly Entity\Repository\StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        // If a timestamp delimiter is added, strip it automatically.
        $id = explode('|', $id, 2)[0];

        $station = $request->getStation();

        $artworkPath = Entity\StationStreamer::getArtworkPath($id);

        $fsConfig = (new StationFilesystems($station))->getConfigFilesystem();
        if ($fsConfig->fileExists($artworkPath)) {
            return $response->withCacheLifetime(Response::CACHE_ONE_YEAR)
                ->streamFilesystemFile($fsConfig, $artworkPath, null, 'inline', false);
        }

        return $response->withRedirect(
            (string)$this->stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
