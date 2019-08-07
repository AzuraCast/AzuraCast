<?php
namespace App\Controller\Api;

use App\Entity;
use App\Event\Radio\LoadNowPlaying;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NowplayingController implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var CacheInterface */
    protected $cache;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param EntityManager $em
     * @param CacheInterface $cache
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EntityManager $em, CacheInterface $cache, EventDispatcher $dispatcher)
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
            LoadNowPlaying::class => [
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
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_NowPlaying"))
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
     *     @OA\JsonContent(ref="#/components/schemas/Api_NowPlaying")
     *   ),
     *   @OA\Response(response=404, description="Station not found")
     * )
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $id
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $id = null): ResponseInterface
    {
        $router = RequestHelper::getRouter($request);

        // Pull NP data from the fastest/first available source using the EventDispatcher.
        $event = new LoadNowPlaying();
        $this->dispatcher->dispatch($event);

        if (!$event->hasNowPlaying()) {
            return ResponseHelper::withJson(
                $response->withStatus(408),
                new Entity\Api\Error(408, 'Now Playing data has not loaded yet. Please try again later.')
            );
        }

        $np = $event->getNowPlaying();

        if (!empty($id)) {
            foreach ($np as $np_row) {
                if ($np_row->station->id == (int)$id || $np_row->station->shortcode === $id) {
                    $np_row->resolveUrls($router->getBaseUrl());
                    $np_row->now_playing->recalculate();
                    return ResponseHelper::withJson($response, $np_row);
                }
            }

            return ResponseHelper::withJson(
                $response->withStatus(404),
                new Entity\Api\Error(404, 'Station not found.')
            );
        }

        // If unauthenticated, hide non-public stations from full view.
        if ($request->getAttribute('user') === null) {
            $np = array_filter($np, function($np_row) {
                return $np_row->station->is_public;
            });
        }

        foreach ($np as $np_row) {
            $np_row->resolveUrls($router->getBaseUrl());
            $np_row->now_playing->recalculate();
        }

        return ResponseHelper::withJson($response, $np);
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
        $nowplaying_db = $this->em->createQuery(/** @lang DQL */'SELECT s.nowplaying FROM App\Entity\Station s WHERE s.is_enabled = 1')
            ->getArrayResult();

        $np = [];
        foreach($nowplaying_db as $np_row) {
            $np[] = $np_row['nowplaying'];
        }

        $event->setNowPlaying($np, 'station');
    }
}
