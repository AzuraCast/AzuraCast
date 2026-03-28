<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Controller\SingleActionInterface;
use App\Customization;
use App\Entity\Repository\StationMediaRepository;
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
    summary: 'Returns the album art for a song, or a generic image.',
    security: [],
    tags: [OpenApi::TAG_PUBLIC_STATIONS],
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
        new OpenApi\Response\SuccessWithImage(
            description: 'The requested album artwork'
        ),
        new OpenApi\Response\Redirect(
            description: 'Image not found; generic filler image.'
        ),
    ]
)]
final readonly class GetArtAction implements SingleActionInterface
{
    public function __construct(
        private StationMediaRepository $mediaRepo,
        private StationFilesystems $stationFilesystems,
        private Customization $customization
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

        return $response->withRedirect((string)$this->customization->getDefaultAlbumArtUrl($station), 302);
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

        $mediaPath = StationMedia::getArtPath($media->unique_id);
        if ($fsMedia->fileExists($mediaPath)) {
            return $mediaPath;
        }

        $folderPath = StationMedia::getFolderArtPath(
            StationMedia::getFolderHashForPath($media->path)
        );
        if ($fsMedia->fileExists($folderPath)) {
            return $folderPath;
        }

        return null;
    }
}
