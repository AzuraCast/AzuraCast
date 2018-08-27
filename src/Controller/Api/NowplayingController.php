<?php
namespace App\Controller\Api;

use App\Cache;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class NowplayingController
{
    /** @var EntityManager */
    protected $em;

    /** @var Cache */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param Cache $cache
     * @see \App\Provider\ApiProvider
     */
    public function __construct(EntityManager $em, Cache $cache)
    {
        $this->em = $em;
        $this->cache = $cache;
    }

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
    public function indexAction(Request $request, Response $response, $id = null): Response
    {
        $response = $response
            ->withHeader('Cache-Control', 'public, max-age=15')
            ->withHeader('X-Accel-Expires', 15); // CloudFlare caching

        // Pull from cache, or load from flatfile otherwise.
        /** @var Entity\Api\NowPlaying[] $np */
        $np = $this->cache->get('api_nowplaying_data', function () {
            $nowplaying_db = $this->em->createQuery('SELECT s.nowplaying FROM '.Entity\Station::class.' s WHERE s.is_enabled = 1')
                ->getArrayResult();

            $np = [];
            foreach($nowplaying_db as $np_row) {
                $np[] = $np_row['nowplaying'];
            }
            return $np;
        });

        // Sanity check for now playing data.
        if (empty($np)) {
            return $response->withJson('Now Playing data has not loaded into the cache. Wait for file reload.', 500);
        }

        if (!empty($id)) {
            foreach ($np as $np_row) {
                if ($np_row->station->id == (int)$id || $np_row->station->shortcode === $id) {
                    $np_row->now_playing->recalculate();
                    return $response->withJson($np_row);
                }
            }

            return $response->withJson('Station not found.', 404);
        }

        // If unauthenticated, hide non-public stations from full view.
        if ($request->getAttribute('user') === null) {
            $np = array_filter($np, function($np_row) {
                return $np_row->station->is_public;
            });
        }

        foreach ($np as $np_row) {
            $np_row->now_playing->recalculate();
        }

        return $response->withJson($np);
    }
}
