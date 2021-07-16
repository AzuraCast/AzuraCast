<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use Psr\Http\Message\ResponseInterface;

class RenameAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        BatchUtilities $batchUtilities
    ): ResponseInterface {
        $from = $request->getParam('file');
        if (empty($from)) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('File not specified.')));
        }

        $to = $request->getParam('newPath');
        if (empty($to)) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('New path not specified.')));
        }

        // No-op if paths match
        if ($from === $to) {
            return $response->withJson(new Entity\Api\Status());
        }

        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $fsMedia->move($from, $to);

        $batchUtilities->handleRename($from, $to, $storageLocation, $fsMedia);

        return $response->withJson(new Entity\Api\Status());
    }
}
