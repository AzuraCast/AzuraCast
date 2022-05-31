<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlayAction
{
    public function __construct(
        private readonly Entity\Repository\StationMediaRepository $mediaRepo
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

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
