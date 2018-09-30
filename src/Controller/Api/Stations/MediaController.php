<?php
namespace App\Controller\Api\Stations;

use App\Url;
use App\Customization;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class MediaController
{
    /** @var EntityManager */
    protected $em;

    /** @var Customization */
    protected $customization;

    /**
     * @param EntityManager $em
     * @param Customization $customization
     */
    public function __construct(EntityManager $em, Customization $customization)
    {
        $this->em = $em;
        $this->customization = $customization;
    }

    /**
     * @OA\Get(path="/station/{station_id}/art/{media_id}",
     *   tags={"Stations: Media"},
     *   description="Returns the album art for a song, or a generic image.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="media_id",
     *     description="The station media ID",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *         type="int64"
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
    public function artAction(Request $request, Response $response, $station_id, $media_id): Response
    {
        $media = $this->em->createQuery('SELECT sm, sa FROM '.Entity\StationMedia::class.' sm JOIN sm.art sa WHERE sm.station_id = :station_id AND sm.unique_id = :media_id')
            ->setParameter('station_id', $station_id)
            ->setParameter('media_id', $media_id)
            ->getOneOrNullResult();

        if ($media instanceof Entity\StationMedia) {

            $art = $media->getArt();

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
