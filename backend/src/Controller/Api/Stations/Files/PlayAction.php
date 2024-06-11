<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationMediaRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlayAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(600);

        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $media = $this->mediaRepo->requireForStation($id, $station);

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
