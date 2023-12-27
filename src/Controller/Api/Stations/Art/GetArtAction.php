<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/art/{media_id}',
    operationId: 'getMediaArt',
    description: 'Returns the album art for a song, or a generic image.',
    tags: ['Stations: Media'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'media_id',
            description: 'The station media unique ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'The requested album artwork'
        ),
        new OA\Response(
            response: 404,
            description: 'Image not found; generic filler image.'
        ),
    ]
)]
final class GetArtAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo,
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

        if (str_contains($mediaId, '-')) {
            $response = $response->withCacheLifetime(
                Response::CACHE_ONE_YEAR,
                Response::CACHE_ONE_DAY
            );
        }

        // If a timestamp delimiter is added, strip it automatically.
        $mediaId = explode('-', $mediaId, 2)[0];

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $mediaPath = $this->getMediaPath($station, $fsMedia, $mediaId);
        if (null !== $mediaPath) {
            return $response->streamFilesystemFile(
                $fsMedia,
                $mediaPath,
                null,
                'inline',
                false
            );
        }

        return $response->withRedirect((string)$this->stationRepo->getDefaultAlbumArtUrl($station), 302);
    }

    private function getMediaPath(
        Station $station,
        ExtendedFilesystemInterface $fsMedia,
        string $mediaId
    ): ?string {
        if (StationMedia::UNIQUE_ID_LENGTH === strlen($mediaId)) {
            $mediaPath = StationMedia::getArtPath($mediaId);

            if ($fsMedia->fileExists($mediaPath)) {
                return $mediaPath;
            }
        }

        $media = $this->mediaRepo->findForStation($mediaId, $station);
        if (!($media instanceof StationMedia)) {
            return null;
        }

        $mediaPath = StationMedia::getArtPath($media->getUniqueId());
        if ($fsMedia->fileExists($mediaPath)) {
            return $mediaPath;
        }

        $folderPath = StationMedia::getFolderArtPath(
            StationMedia::getFolderHashForPath($media->getPath())
        );
        if ($fsMedia->fileExists($folderPath)) {
            return $folderPath;
        }

        return null;
    }
}
