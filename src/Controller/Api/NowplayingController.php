<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity;
use App\Event\Radio\LoadNowPlaying;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NowplayingController implements EventSubscriberInterface
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected CacheInterface $cache,
        protected EventDispatcherInterface $dispatcher
    ) {
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
    public function __invoke(
        ServerRequest $request,
        Response $response,
        $station_id = null
    ): ResponseInterface {
        $router = $request->getRouter();

        // Pull NP data from the fastest/first available source using the EventDispatcher.
        $event = new LoadNowPlaying();
        $this->dispatcher->dispatch($event);

        if (!$event->hasNowPlaying()) {
            return $response->withStatus(408)
                ->withJson(new Entity\Api\Error(408, 'Now Playing data has not loaded yet. Please try again later.'));
        }

        if (!empty($station_id)) {
            $npStation = $event->getForStation($station_id);
            if (null !== $npStation) {
                $npStation->resolveUrls($router->getBaseUrl());
                return $response->withJson($npStation);
            }

            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        // If unauthenticated, hide non-public stations from full view.
        $np = ($request->getAttribute('user') === null)
            ? $event->getAllPublic()
            : $event->getNowPlaying();

        foreach ($np as $npRow) {
            $npRow->resolveUrls($router->getBaseUrl());
        }

        return $response->withJson($np);
    }

    public function loadFromCache(LoadNowPlaying $event): void
    {
        $event->setNowPlaying((array)$this->cache->get('nowplaying'), 'redis');
    }

    public function loadFromSettings(LoadNowPlaying $event): void
    {
        $settings = $this->settingsRepo->readSettings();
        $event->setNowPlaying((array)$settings->getNowplaying(), 'settings');
    }

    public function loadFromStations(LoadNowPlaying $event): void
    {
        $nowplaying_db = $this->em->createQuery(
            <<<'DQL'
                SELECT s.nowplaying FROM App\Entity\Station s WHERE s.is_enabled = 1
            DQL
        )->getArrayResult();

        $np = [];
        foreach ($nowplaying_db as $np_row) {
            $np[] = $np_row['nowplaying'];
        }

        $event->setNowPlaying($np, 'station');
    }
}
