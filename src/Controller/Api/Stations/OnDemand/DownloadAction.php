<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DownloadAction
{
    public function __construct(
        private readonly Entity\Repository\StationMediaRepository $mediaRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableOnDemand()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not support on-demand streaming.')));
        }

        $media = $this->mediaRepo->requireByUniqueId($media_id, $station);

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        set_time_limit(600);
        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
