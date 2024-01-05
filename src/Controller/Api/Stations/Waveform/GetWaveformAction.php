<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Waveform;

use App\Controller\SingleActionInterface;
use App\Controller\Traits\ResponseHasCacheLifetime;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationMedia;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetWaveformAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationFilesystems $stationFilesystems
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

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        if (StationMedia::UNIQUE_ID_LENGTH === strlen($mediaId)) {
            $waveformPath = StationMedia::getWaveformPath($mediaId);
            if ($fsMedia->fileExists($waveformPath)) {
                return $response->streamFilesystemFile($fsMedia, $waveformPath, null, 'inline');
            }
        }

        $media = $this->mediaRepo->requireByUniqueId($mediaId, $station);

        $waveformPath = StationMedia::getWaveformPath($media->getUniqueId());
        if (!$fsMedia->fileExists($waveformPath)) {
            $this->mediaRepo->updateWaveform($media);
        }

        return $response->streamFilesystemFile($fsMedia, $waveformPath, null, 'inline');
    }
}
