<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DownloadAction
{
    public function __construct(
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $path = $request->getParam('file');

        if (!$fsMedia->fileExists($path)) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        return $response->streamFilesystemFile($fsMedia, $path);
    }
}
