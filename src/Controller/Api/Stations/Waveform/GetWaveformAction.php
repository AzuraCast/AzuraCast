<?php

namespace App\Controller\Api\Stations\Waveform;

use App\Entity\Api\Error;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationMedia;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetWaveformAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem,
        StationMediaRepository $mediaRepo,
        $media_id
    ): ResponseInterface {
        $response = $response->withCacheLifetime(Response::CACHE_ONE_YEAR);

        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        // If a timestamp delimiter is added, strip it automatically.
        $media_id = explode('-', $media_id)[0];

        if (StationMedia::UNIQUE_ID_LENGTH === strlen($media_id)) {
            $waveformUri = StationMedia::getWaveformUri($media_id);
            if ($fs->has($waveformUri)) {
                return $fs->streamToResponse($response, $waveformUri, null, 'inline');
            }
        }

        $media = $mediaRepo->findByUniqueId($media_id, $station);
        if (!($media instanceof StationMedia)) {
            return $response->withStatus(500)->withJson(new Error(500, 'Media not found.'));
        }

        $waveformUri = StationMedia::getWaveformUri($media->getUniqueId());
        if (!$fs->has($waveformUri)) {
            $mediaRepo->updateWaveform($media);
        }

        return $fs->streamToResponse($response, $waveformUri, null, 'inline');
    }
}
