<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PlayAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id,
        Entity\Repository\StationMediaRepository $mediaRepo
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $media = $mediaRepo->find($id, $station);

        if (!$media instanceof Entity\StationMedia) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
