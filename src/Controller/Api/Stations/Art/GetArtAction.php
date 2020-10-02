<?php
namespace App\Controller\Api\Stations\Art;

use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\StationMedia;
use App\Flysystem\Filesystem;
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
     * @param Filesystem $filesystem
     * @param StationRepository $stationRepo
     * @param StationMediaRepository $mediaRepo
     * @param string $media_id
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem,
        StationRepository $stationRepo,
        StationMediaRepository $mediaRepo,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $defaultArtRedirect = $response->withRedirect($stationRepo->getDefaultAlbumArtUrl($station), 302);
        $fs = $filesystem->getForStation($station);

        // If a timestamp delimiter is added, strip it automatically.
        $media_id = explode('-', $media_id)[0];

        if (StationMedia::UNIQUE_ID_LENGTH === strlen($media_id)) {
            $response = $response->withCacheLifetime(Response::CACHE_ONE_YEAR);
            $mediaPath = Filesystem::PREFIX_ALBUM_ART . '://' . $media_id . '.jpg';
        } else {
            $media = $mediaRepo->find($media_id, $station);
            if ($media instanceof StationMedia) {
                $mediaPath = $media->getArtPath();
            } else {
                return $defaultArtRedirect;
            }
        }

        if ($fs->has($mediaPath)) {
            return $response->withFlysystemFile($fs, $mediaPath, null, 'inline');
        }

        return $defaultArtRedirect;
    }
}
