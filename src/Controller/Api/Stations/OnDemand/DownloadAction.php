<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Repository\StationMediaRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DownloadAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationFilesystems $stationFilesystems,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $mediaId */
        $mediaId = $params['media_id'];

        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableOnDemand()) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('This station does not support on-demand streaming.')));
        }

        $media = $this->mediaRepo->requireByUniqueId($mediaId, $station);

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        set_time_limit(600);
        return $response->streamFilesystemFile($fsMedia, $media->getPath());
    }
}
