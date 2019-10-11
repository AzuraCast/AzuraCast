<?php
namespace App\Controller\Api\Stations\Art;

use App\Customization;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationMedia;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
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
     * @param Customization $customization
     * @param Filesystem $filesystem
     * @param StationMediaRepository $mediaRepo
     * @param string $media_id
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Customization $customization,
        Filesystem $filesystem,
        StationMediaRepository $mediaRepo,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        if (StationMedia::UNIQUE_ID_LENGTH === strlen($media_id)) {
            $mediaPath = 'albumart://' . $media_id . '.jpg';
        } else {
            $media = $mediaRepo->find($media_id, $station);
            if ($media instanceof StationMedia) {
                $mediaPath = $media->getArtPath();
            }
        }

        if ($fs->has($mediaPath)) {
            $file_meta = $fs->getMetadata($mediaPath);
            $art = $fs->readStream($mediaPath);

            if (is_resource($art)) {
                return $response->withFile($art, 'image/jpeg')
                    ->withCacheLifetime(Response::CACHE_ONE_YEAR)
                    ->withHeader('Content-Length', $file_meta['size']);
            }
        }

        return $response->withRedirect($customization->getDefaultAlbumArtUrl(), 302);
    }
}
