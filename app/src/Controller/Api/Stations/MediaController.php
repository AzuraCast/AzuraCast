<?php
namespace Controller\Api\Stations;

use App\Url;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class MediaController
{
    /** @var EntityManager */
    protected $em;

    /** @var Url */
    protected $url;

    /**
     * MediaController constructor.
     * @param EntityManager $em
     * @param Url $url
     */
    public function __construct(EntityManager $em, Url $url)
    {
        $this->em = $em;
        $this->url = $url;
    }

    /**
     * @SWG\Get(path="/station/{station_id}/art/{media_id}",
     *   tags={"Stations: Media"},
     *   description="Returns the album art for a song, or a generic image.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Parameter(
     *     name="media_id",
     *     description="The station media ID",
     *     type="integer",
     *     format="int64",
     *     in="path",
     *     required=true
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="The requested album artwork"
     *   ),
     *   @SWG\Response(
     *     response=404,
     *     description="Image not found; generic filler image."
     *   )
     * )
     */
    public function artAction(Request $request, Response $response, $station_id, $media_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $media = $this->em->createQuery('SELECT sm, sa FROM Entity\StationMedia sm JOIN sm.art sa WHERE sm.station_id = :station_id AND sm.unique_id = :media_id')
            ->setParameter('station_id', $station->getId())
            ->setParameter('media_id', $media_id)
            ->getOneOrNullResult();

        if ($media instanceof Entity\StationMedia) {

            $art = $media->getArt();

            if (is_resource($art)) {
                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'image/jpeg')
                    ->withHeader('Cache-Control', 'public, max-age=31536000')
                    ->withBody(new \Slim\Http\Stream($art));
            }
        }

        return $response->withRedirect($this->url->content('img/generic_song.jpg'), 302);
    }
}