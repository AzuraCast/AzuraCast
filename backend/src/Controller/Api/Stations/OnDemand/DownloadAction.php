<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationPlaylistMedia;
use App\Exception\NotFoundException;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/ondemand/download/{media_id}',
    operationId: 'getStationOnDemandDownload',
    summary: 'Download an on-demand playlist file by media unique ID.',
    security: [],
    tags: [OpenApi::TAG_PUBLIC_STATIONS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'media_id',
            description: 'The media unique ID to download.',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
        ),
    ],
    responses: [
        new OpenApi\Response\SuccessWithDownload(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class DownloadAction implements SingleActionInterface
{
    public function __construct(
        private StationMediaRepository $mediaRepo,
        private StationFilesystems $stationFilesystems,
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

        $media = $this->mediaRepo->requireByUniqueId($mediaId, $station);

        // Check if the media is associated with an on-demand playlist.
        $isValid = array_any(
            $media->playlists->toArray(),
            function (StationPlaylistMedia $spm) {
                $playlist = $spm->playlist;
                return $playlist->is_enabled && $playlist->include_in_on_demand;
            }
        );

        if (!$isValid) {
            throw NotFoundException::file();
        }

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        set_time_limit(600);
        return $response->streamFilesystemFile($fsMedia, $media->path);
    }
}
