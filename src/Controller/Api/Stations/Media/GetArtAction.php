<?php
namespace App\Controller\Api\Stations\Media;

use App\Customization;
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
     * @param string $media_id
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Customization $customization,
        Filesystem $filesystem,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        $media_path = 'albumart://' . $media_id . '.jpg';

        if ($fs->has($media_path)) {
            $file_meta = $fs->getMetadata($media_path);
            $art = $fs->readStream($media_path);

            if (is_resource($art)) {
                return $response->withFile($art, 'image/jpeg')
                    ->withCacheLifetime(Response::CACHE_ONE_YEAR)
                    ->withHeader('Content-Length', $file_meta['size']);
            }
        }

        return $response->withRedirect($this->customization->getDefaultAlbumArtUrl(), 302);
    }
}
