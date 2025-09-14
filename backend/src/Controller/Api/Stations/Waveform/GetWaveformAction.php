<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Waveform;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationMedia;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/waveform/{media_id}',
    operationId: 'getStationMediaWaveform',
    summary: 'Get waveform data for a media ID (for the Visual Cue Editor).',
    tags: [OpenApi::TAG_STATIONS_MEDIA],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Media ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        // TODO API Response
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetWaveformAction implements SingleActionInterface
{
    public function __construct(
        private StationMediaRepository $mediaRepo,
        private StationFilesystems $stationFilesystems
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

        $waveformPath = StationMedia::getWaveformPath($media->unique_id);
        if (!$fsMedia->fileExists($waveformPath)) {
            $this->mediaRepo->updateWaveform($media);
        }

        return $response->streamFilesystemFile($fsMedia, $waveformPath, null, 'inline');
    }
}
