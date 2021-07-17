<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $media_id,
        Entity\Repository\StationMediaRepository $mediaRepo
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableOnDemand()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not support on-demand streaming.')));
        }

        $media = $mediaRepo->findByUniqueId($media_id, $station);

        if (!($media instanceof Entity\StationMedia)) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        set_time_limit(600);
        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
