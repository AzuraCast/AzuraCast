<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Flysystem\ExtendedFilesystemInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/art/{media_id}',
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
final class GetArtAction
{
    public function __construct(
        private readonly Entity\Repository\StationRepository $stationRepo,
        private readonly Entity\Repository\StationMediaRepository $mediaRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        if (str_contains($media_id, '-')) {
            $response = $response->withCacheLifetime(Response::CACHE_ONE_YEAR);
        }

        // If a timestamp delimiter is added, strip it automatically.
        $media_id = explode('-', $media_id, 2)[0];

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $mediaPath = $this->getMediaPath($station, $fsMedia, $media_id);
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
        Entity\Station $station,
        ExtendedFilesystemInterface $fsMedia,
        string $media_id
    ): ?string {
        if (Entity\StationMedia::UNIQUE_ID_LENGTH === strlen($media_id)) {
            $mediaPath = Entity\StationMedia::getArtPath($media_id);

            if ($fsMedia->fileExists($mediaPath)) {
                return $mediaPath;
            }
        }

        $media = $this->mediaRepo->findForStation($media_id, $station);
        if (!($media instanceof Entity\StationMedia)) {
            return null;
        }

        $mediaPath = Entity\StationMedia::getArtPath($media->getUniqueId());
        if ($fsMedia->fileExists($mediaPath)) {
            return $mediaPath;
        }

        $folderPath = Entity\StationMedia::getFolderArtPath(
            Entity\StationMedia::getFolderHashForPath($media->getPath())
        );
        if ($fsMedia->fileExists($folderPath)) {
            return $folderPath;
        }

        return null;
    }
}
