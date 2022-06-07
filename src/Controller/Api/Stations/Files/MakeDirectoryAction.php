<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Flysystem\UnableToCreateDirectory;
use Psr\Http\Message\ResponseInterface;

final class MakeDirectoryAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $currentDir = $request->getParam('currentDirectory', '');
        $newDirName = $request->getParam('name', '');

        if (empty($newDirName)) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, __('No directory specified')));
        }

        $station = $request->getStation();

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $newDir = $currentDir . '/' . $newDirName;

        try {
            $fsMedia->createDirectory($newDir);
        } catch (UnableToCreateDirectory $e) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, $e->getMessage()));
        }

        return $response->withJson(Entity\Api\Status::created());
    }
}
