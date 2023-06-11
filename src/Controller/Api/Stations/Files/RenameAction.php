<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use Psr\Http\Message\ResponseInterface;

final class RenameAction implements SingleActionInterface
{
    public function __construct(
        private readonly BatchUtilities $batchUtilities,
        private readonly StationFilesystems $stationFilesystems,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $from = $request->getParam('file');
        if (empty($from)) {
            return $response->withStatus(500)
                ->withJson(new Error(500, __('File not specified.')));
        }

        $to = $request->getParam('newPath');
        if (empty($to)) {
            return $response->withStatus(500)
                ->withJson(new Error(500, __('New path not specified.')));
        }

        // No-op if paths match
        if ($from === $to) {
            return $response->withJson(Status::updated());
        }

        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);
        $fsMedia->move($from, $to);

        $this->batchUtilities->handleRename($from, $to, $storageLocation, $fsMedia);

        return $response->withJson(Status::updated());
    }
}
