<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Entity\StationStreamer;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetArtAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $artworkPath = StationStreamer::getArtworkPath($id);

        $fsConfig = StationFilesystems::buildConfigFilesystem($station);
        if ($fsConfig->fileExists($artworkPath)) {
            return $response->streamFilesystemFile($fsConfig, $artworkPath, null, 'inline', false);
        }

        return $response->withRedirect(
            (string)$this->stationRepo->getDefaultAlbumArtUrl($station),
            302
        );
    }
}
