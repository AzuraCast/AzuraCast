<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Waveform;

use App\Entity\Api\Error;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationMedia;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetWaveformAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        StationMediaRepository $mediaRepo,
        string $media_id
    ): ResponseInterface {
        $response = $response->withCacheLifetime(Response::CACHE_ONE_YEAR);

        $station = $request->getStation();

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        // If a timestamp delimiter is added, strip it automatically.
        $media_id = explode('-', $media_id, 2)[0];

        if (StationMedia::UNIQUE_ID_LENGTH === strlen($media_id)) {
            $waveformPath = StationMedia::getWaveformPath($media_id);
            if ($fsMedia->fileExists($waveformPath)) {
                return $response->streamFilesystemFile($fsMedia, $waveformPath, null, 'inline');
            }
        }

        $media = $mediaRepo->findByUniqueId($media_id, $station);
        if (!($media instanceof StationMedia)) {
            return $response->withStatus(500)->withJson(new Error(500, 'Media not found.'));
        }

        $waveformPath = StationMedia::getWaveformPath($media->getUniqueId());
        if (!$fsMedia->fileExists($waveformPath)) {
            $mediaRepo->updateWaveform($media);
        }

        return $response->streamFilesystemFile($fsMedia, $waveformPath, null, 'inline');
    }
}
