<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Flysystem\UnableToCreateDirectory;
use Psr\Http\Message\ResponseInterface;

final class MakeDirectoryAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentDir = $request->getParam('currentDirectory', '');
        $newDirName = $request->getParam('name', '');

        if (empty($newDirName)) {
            return $response->withStatus(400)
                ->withJson(new Error(400, __('No directory specified')));
        }

        $station = $request->getStation();

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $newDir = $currentDir . '/' . $newDirName;

        try {
            $fsMedia->createDirectory($newDir);
        } catch (UnableToCreateDirectory $e) {
            return $response->withStatus(400)
                ->withJson(new Error(400, $e->getMessage()));
        }

        return $response->withJson(Status::created());
    }
}
