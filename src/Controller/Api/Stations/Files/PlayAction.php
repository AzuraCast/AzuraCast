<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use App\Entity\Repository\StationMediaRepository;

final class PlayAction
{
    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $media = $this->mediaRepo->requireForStation($id, $station);

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
