<?php
namespace App\Controller\Api\Stations;

use App\Customization;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Radio\Filesystem;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Stream;

class ArtController
{
    /** @var Customization */
    protected $customization;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param Customization $customization
     * @param Filesystem $filesystem
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
     *   @OA\Response(response=200, description="The requested album artwork"),
     *   @OA\Response(response=404, description="Image not found; generic filler image.")
     * )
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string|int $station_id
     * @param string $media_id
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $station_id, $media_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $filesystem = $this->filesystem->getForStation($station);

        $media_path = 'albumart://'.$media_id.'.jpg';

        if ($filesystem->has($media_path)) {
            $file_meta = $filesystem->getMetadata($media_path);
            $art = $filesystem->readStream($media_path);

            if (is_resource($art)) {
                return ResponseHelper::withCacheLifetime($response, ResponseHelper::CACHE_ONE_YEAR)
                    ->withHeader('Content-Type', 'image/jpeg')
                    ->withHeader('Content-Length', $file_meta['size'])
                    ->withBody(new Stream($art));
            }
        }

        return ResponseHelper::withRedirect($response, $this->customization->getDefaultAlbumArtUrl(), 302);
    }
}
