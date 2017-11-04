<?php
namespace Controller\Api;

use Entity;

class MediaController extends BaseController
{
    /**
     * @SWG\Get(path="/station/{station_id}/art/{media_id}",
     *   tags={"Stations: Media"},
     *   description="Returns the album art for a song, or a generic image.",
     *   parameters={},
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
    public function artAction()
    {
        try {
            $station = $this->getStation();
        } catch(\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        $media = $this->em->createQuery('SELECT sm FROM Entity\StationMedia sm WHERE sm.station_id = :station_id AND sm.unique_id = :media_id')
            ->setParameter('station_id', $station->getId())
            ->setParameter('media_id', $this->getParam('media_id'))
            ->getOneOrNullResult();

        if ($media instanceof Entity\StationMedia) {

            $art = $media->getArt();

            if (is_resource($art)) {
                return $this->response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'image/jpeg')
                    ->withBody(new \Slim\Http\Stream($art));
            }
        }

        $missing_image_url = APP_INCLUDE_ROOT.'/resources/generic_song.jpg';

        return $this->response
            ->withStatus(404)
            ->withHeader('Content-Type', 'image/jpeg')
            ->withBody(new \Slim\Http\Stream(fopen($missing_image_url, 'r')));
    }
}