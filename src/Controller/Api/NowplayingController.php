<?php

namespace App\Controller\Api;

use App\Entity;
use App\Event\Radio\LoadNowPlaying;
use App\EventDispatcher;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NowplayingController implements EventSubscriberInterface
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected CacheInterface $cache;

    protected EventDispatcher $dispatcher;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        CacheInterface $cache,
        EventDispatcher $dispatcher
    ) {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
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
     * @return mixed[] The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoadNowPlaying::class => [
                ['loadFromCache', 5],
                ['loadFromSettings', 0],
                ['loadFromStations', -5],
            ],
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
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string|null $station_id
     */
    public function __invoke(ServerRequest $request, Response $response, $station_id = null): ResponseInterface
    {
        $router = $request->getRouter();

        // Pull NP data from the fastest/first available source using the EventDispatcher.
        $event = new LoadNowPlaying();
        $this->dispatcher->dispatch($event);

        if (!$event->hasNowPlaying()) {
            return $response->withStatus(408)
                ->withJson(new Entity\Api\Error(408, 'Now Playing data has not loaded yet. Please try again later.'));
        }

        $np = $event->getNowPlaying();

        if (!empty($station_id)) {
            foreach ($np as $np_row) {
                if ($np_row->station->id == (int)$station_id || $np_row->station->shortcode === $station_id) {
                    $np_row->resolveUrls($router->getBaseUrl());
                    $np_row->now_playing->recalculate();
                    return $response->withJson($np_row);
                }
            }

            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, 'Station not found.'));
        }

        // If unauthenticated, hide non-public stations from full view.
        if ($request->getAttribute('user') === null) {
            $np = array_filter($np, function ($np_row) {
                return $np_row->station->is_public;
            });

            // Prevent NP array from returning as an object.
            $np = array_values($np);
        }

        foreach ($np as $np_row) {
            $np_row->resolveUrls($router->getBaseUrl());
            $np_row->now_playing->recalculate();
        }

        return $response->withJson($np);
    }

    public function loadFromCache(LoadNowPlaying $event): void
    {
        $event->setNowPlaying((array)$this->cache->get(Entity\Settings::NOWPLAYING), 'redis');
    }

    public function loadFromSettings(LoadNowPlaying $event): void
    {
        $event->setNowPlaying((array)$this->settingsRepo->getSetting(Entity\Settings::NOWPLAYING), 'settings');
    }

    public function loadFromStations(LoadNowPlaying $event): void
    {
        $nowplaying_db = $this->em
            ->createQuery(/** @lang DQL */ 'SELECT s.nowplaying FROM App\Entity\Station s WHERE s.is_enabled = 1')
            ->getArrayResult();

        $np = [];
        foreach ($nowplaying_db as $np_row) {
            $np[] = $np_row['nowplaying'];
        }

        $event->setNowPlaying($np, 'station');
    }
}
