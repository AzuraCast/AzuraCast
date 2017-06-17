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
        $this->setCacheLifetime(15);

        // Pull from cache, or load from flatfile otherwise.

        /** @var \App\Cache $cache */
        $cache = $this->di->get('cache');

        $np_cached = $cache->get('api_nowplaying_data', function () {
            return $this->di['em']->getRepository(Entity\Settings::class)->getSetting('nowplaying');
        });

        // Convert back into a large array if it's serialized as the new API models
        $np = [];
        foreach ((array)$np_cached as $np_row) {
            if ($np_row instanceof Entity\Api\NowPlaying) {
                $np_row = json_decode(json_encode($np_row), true);
            }
            $np[] = $np_row;
        }

        // Sanity check for now playing data.
        if (empty($np)) {
            return $this->returnError('Now Playing data has not loaded into the cache. Wait for file reload.', 500);
        }

        if ($this->hasParam('station')) {
            $id = $this->getParam('station');

            foreach ($np as $key => $np_row) {
                if ($np_row['station']['id'] == (int)$id || $np_row['station']['shortcode'] === $id) {
                    return $this->returnSuccess($np_row);
                }
            }

            return $this->returnError('Station not found.', 404);
        } else {
            return $this->returnSuccess($np);
        }
    }
}