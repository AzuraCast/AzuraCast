<?php

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class GetArtAction
{
    /**
     * @OA\Get(path="/station/{station_id}/art/{media_id}",
     *   tags={"Stations: Media"},
     *   description="Returns the album art for a song, or a generic image.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="media_id",
     *     description="The station media unique ID",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="The requested album artwork"),
     *   @OA\Response(response=404, description="Image not found; generic filler image.")
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param Entity\Repository\StationRepository $stationRepo
     * @param Entity\Repository\StationMediaRepository $mediaRepo
     * @param string $media_id
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\StationMediaRepository $mediaRepo,
        string $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $fsStation = new StationFilesystems($station);
        $fsMedia = $fsStation->getMediaFilesystem();

        $defaultArtRedirect = $response->withRedirect($stationRepo->getDefaultAlbumArtUrl($station), 302);

        // If a timestamp delimiter is added, strip it automatically.
        $media_id = explode('-', $media_id)[0];

        if (Entity\StationMedia::UNIQUE_ID_LENGTH === strlen($media_id)) {
            $response = $response->withCacheLifetime(Response::CACHE_ONE_YEAR);
            $mediaPath = Entity\StationMedia::getArtPath($media_id);
        } else {
            $media = $mediaRepo->find($media_id, $station);
            if ($media instanceof Entity\StationMedia) {
                $mediaPath = Entity\StationMedia::getArtPath($media->getUniqueId());
            } else {
                return $defaultArtRedirect;
            }
        }

        if ($fsMedia->fileExists($mediaPath)) {
            return $fsMedia->streamToResponse($response, $mediaPath, null, 'inline');
        }

        return $defaultArtRedirect;
    }
}
