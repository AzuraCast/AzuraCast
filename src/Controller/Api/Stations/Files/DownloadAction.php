<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity\Api\Error;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $path = $request->getParam('file');

        if (!$fsMedia->fileExists($path)) {
            return $response->withStatus(404)
                ->withJson(new Error(404, 'File not found.'));
        }

        return $response->streamFilesystemFile($fsMedia, $path);
    }
}
