<?php
namespace App\Controller\Api;

use Azura\Cache;
use App\Event\Radio\LoadNowPlaying;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NowplayingController implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var Cache */
    protected $cache;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param EntityManager $em
     * @param Cache $cache
     * @param EventDispatcher $dispatcher
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(EntityManager $em, Cache $cache, EventDispatcher $dispatcher)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            LoadNowPlaying::NAME => [
                ['loadFromCache', 5],
                ['loadFromSettings', 0],
                ['loadFromStations', -5],
            ]
        ];
    }

    /**
     * @OA\Get(path="/nowplaying",
     *   tags={"Now Playing"},
     *   description="Returns a full summary of all stations' current state.",
     *   parameters={},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/NowPlaying")
     *     )
     *   )
     * )
     *
     * @OA\Get(path="/nowplaying/{station_id}",
     *   tags={"Now Playing"},
     *   description="Returns a full summary of the specified station's current state.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       ref="#/components/schemas/NowPlaying"
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found")
     * )
     */
    public function __invoke(Request $request, Response $response, $id = null): Response
    {
        $response = $response
            ->withHeader('Cache-Control', 'public, max-age=15')
            ->withHeader('X-Accel-Expires', 15); // CloudFlare caching

        // Pull NP data from the fastest/first available source using the EventDispatcher.
        $event = new LoadNowPlaying();
        $this->dispatcher->dispatch(LoadNowPlaying::NAME, $event);

        if (!$event->hasNowPlaying()) {
            return $response->withJson('Now Playing data has not loaded yet. Please try again later.', 408);
        }

        $np = $event->getNowPlaying();

        if (!empty($id)) {
            foreach ($np as $np_row) {
                if ($np_row->station->id == (int)$id || $np_row->station->shortcode === $id) {
                    $np_row->resolveUrls($request->getRouter());
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
            $np_row->resolveUrls($request->getRouter());
            $np_row->now_playing->recalculate();
        }

        return $response->withJson($np);
    }

    public function loadFromCache(LoadNowPlaying $event)
    {
        $event->setNowPlaying((array)$this->cache->get('api_nowplaying_data'), 'redis');
    }

    public function loadFromSettings(LoadNowPlaying $event)
    {
        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $event->setNowPlaying((array)$settings_repo->getSetting('nowplaying'), 'settings');
    }

    public function loadFromStations(LoadNowPlaying $event)
    {
        $nowplaying_db = $this->em->createQuery('SELECT s.nowplaying FROM '.Entity\Station::class.' s WHERE s.is_enabled = 1')
            ->getArrayResult();

        $np = [];
        foreach($nowplaying_db as $np_row) {
            $np[] = $np_row['nowplaying'];
        }

        $event->setNowPlaying($np, 'station');
    }
}
