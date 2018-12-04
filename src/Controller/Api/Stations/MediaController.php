<?php
namespace App\Controller\Api\Stations;

use App\Radio\Filesystem;
use App\Customization;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class MediaController
{
    /** @var Customization */
    protected $customization;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param Customization $customization
     * @param Filesystem $filesystem
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(Customization $customization, Filesystem $filesystem)
    {
        $this->customization = $customization;
        $this->filesystem = $filesystem;
    }

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
     *   @OA\Response(
     *     response=200,
     *     description="The requested album artwork"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Image not found; generic filler image."
     *   )
     * )
     */
    public function artAction(Request $request, Response $response, $station_id, $media_id): ResponseInterface
    {
        $station = $request->getStation();
        $filesystem = $this->filesystem->getForStation($station);

        $media_path = 'albumart://'.$media_id.'.jpg';

        if ($filesystem->has($media_path)) {
            $art = $filesystem->readStream($media_path);

            if (is_resource($art)) {
                return $response
                    ->withCacheLifetime(Response::CACHE_ONE_YEAR)
                    ->withHeader('Content-Type', 'image/jpeg')
                    ->withBody(new \Slim\Http\Stream($art));
            }
        }

        return $response->withRedirect($this->customization->getDefaultAlbumArtUrl(), 302);
    }
}
