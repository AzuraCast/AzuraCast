<?php

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
                ->withJson(new Entity\Api\Error(404, 'Not Found'));
        }

        $fsStation = new StationFilesystems($station);
        $fsMedia = $fsStation->getMediaFilesystem();

        return $fsMedia->streamToResponse($response, $media->getPath());
    }
}
