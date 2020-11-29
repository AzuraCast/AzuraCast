<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class MakeDirectoryAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem
    ): ResponseInterface {
        $currentDir = $request->getParam('currentDirectory', '');
        $newDirName = $request->getParam('name', '');

        if (empty($newDirName)) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, __('No directory specified')));
        }

        $station = $request->getStation();
        $fs = $filesystem->getPrefixedAdapterForStation($station, FilesystemManager::PREFIX_MEDIA, true);

        $newDir = $currentDir . '/' . $newDirName;
        if (!$fs->createDir($newDir)) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('Directory "%s" was not created', $newDir)));
        }

        return $response->withJson(new Entity\Api\Status());
    }
}
