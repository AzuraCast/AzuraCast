<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity\Api\Error;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();
        $fs = $storageLocation->getFilesystem();

        $path = $request->getParam('file');

        if (!$fs->has($path)) {
            return $response->withStatus(404)
                ->withJson(new Error(404, 'File not found.'));
        }

        return $fs->streamToResponse($response, $path);
    }
}
