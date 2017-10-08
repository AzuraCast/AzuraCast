<?php
namespace Controller\Api;

use Entity;

class NowplayingController extends BaseController
{
    /**
     * @SWG\Get(path="/nowplaying",
     *   tags={"Now Playing"},
     *   description="Returns a full summary of all stations' current state.",
     *   parameters={},
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       type="array",
     *       @SWG\Items(ref="#/definitions/NowPlaying")
     *     )
     *   )
     * )
     *
     * @SWG\Get(path="/nowplaying/{station_id}",
     *   tags={"Now Playing"},
     *   description="Returns a full summary of the specified station's current state.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       ref="#/definitions/NowPlaying"
     *     )
     *   ),
     *   @SWG\Response(response=404, description="Station not found")
     * )
     */
    public function indexAction()
    {
        $this->response = $this->response->withHeader('Cache-Control', 'public, max-age=15')
            ->withHeader('X-Accel-Expires', 15); // CloudFlare caching

        // Pull from cache, or load from flatfile otherwise.

        /** @var \App\Cache $cache */
        $cache = $this->di->get('cache');

        /** @var Entity\Api\NowPlaying[] $np */
        $np = $cache->get('api_nowplaying_data', function () {
            $nowplaying_db = $this->em->createQuery('SELECT s.nowplaying FROM Entity\Station s')
                ->getArrayResult();

            $np = [];
            foreach($nowplaying_db as $np_row) {
                $np[] = $np_row['nowplaying'];
            }
            return $np;
        });

        // Sanity check for now playing data.
        if (empty($np)) {
            return $this->returnError('Now Playing data has not loaded into the cache. Wait for file reload.', 500);
        }

        if ($this->hasParam('station')) {
            $id = $this->getParam('station');

            foreach ($np as $np_row) {
                if ($np_row->station->id == (int)$id || $np_row->station->shortcode === $id) {
                    $np_row->now_playing->recalculate();
                    return $this->returnSuccess($np_row);
                }
            }

            return $this->returnError('Station not found.', 404);
        } else {
            $np = array_filter($np, function($np_row) {
                return $np_row->station->is_public;
            });

            foreach ($np as $np_row) {
                $np_row->now_playing->recalculate();
            }

            return $this->returnSuccess($np);
        }
    }
}