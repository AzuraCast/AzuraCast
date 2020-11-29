<?php

namespace App\Controller\Frontend;

use App\Acl;
use App\Entity;
use App\Event;
use App\EventDispatcher;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class DashboardController
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Acl $acl;

    protected CacheInterface $cache;

    protected Router $router;

    protected Adapters $adapter_manager;

    protected EventDispatcher $dispatcher;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Acl $acl,
        CacheInterface $cache,
        Adapters $adapter_manager,
        EventDispatcher $dispatcher
    ) {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
        $this->acl = $acl;
        $this->cache = $cache;
        $this->adapter_manager = $adapter_manager;
        $this->dispatcher = $dispatcher;
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $view = $request->getView();
        $user = $request->getUser();
        $router = $request->getRouter();

        $show_admin = $this->acl->userAllowed($user, Acl::GLOBAL_VIEW);

        /** @var Entity\Station[] $stations */
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        // Don't show stations the user can't manage.
        $stations = array_filter($stations, function ($station) use ($user) {
            /** @var Entity\Station $station */
            return $station->isEnabled() &&
                $this->acl->userAllowed($user, Acl::STATION_VIEW, $station->getId());
        });

        if (empty($stations) && !$show_admin) {
            return $view->renderToResponse($response, 'frontend/index/noaccess');
        }

        // Get administrator notifications.
        $notification_event = new Event\GetNotifications($request);
        $this->dispatcher->dispatch($notification_event);

        $notifications = $notification_event->getNotifications();

        $view_stations = [];
        $station_ids = [];

        // Generate initial data for station dashboard view.
        foreach ($stations as $row) {
            $frontend_adapter = $this->adapter_manager->getFrontendAdapter($row);

            $np = [
                'now_playing' => [
                    'song' => [
                        'title' => '',
                        'artist' => '',
                    ],
                ],
                'listeners' => [
                    'current' => 0,
                ],
            ];

            $station_np = $row->getNowplaying();
            if ($station_np instanceof Entity\Api\NowPlaying) {
                $np['now_playing']['song']['title'] = $station_np->now_playing->song->title;
                $np['now_playing']['song']['artist'] = $station_np->now_playing->song->artist;
                $np['listeners']['current'] = $station_np->listeners->current;
            }

            $view_stations[$row->getId()] = [
                'station' => [
                    'id' => $row->getId(),
                    'name' => $row->getName(),
                    'short_name' => $row->getShortName(),
                ],
                'public_url' => (string)$router->named('public:index', ['station_id' => $row->getShortName()]),
                'manage_url' => (string)$router->named('stations:index:index', ['station_id' => $row->getId()]),
                'stream_url' => (string)$frontend_adapter->getStreamUrl($row),
                'np' => $np,
            ];
            $station_ids[] = $row->getId();
        }

        // Detect current analytics level.
        $analytics_level = $this->settingsRepo->getSetting(
            Entity\Settings::LISTENER_ANALYTICS,
            Entity\Analytics::LEVEL_ALL
        );

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            $metrics = null;
        } else {
            // Generate unique cache ID for stations.
            $stats_cache_stations = [];
            foreach ($stations as $station) {
                $stats_cache_stations[$station->getId()] = $station->getId();
            }

            $cache_name = 'homepage_metrics_' . implode(',', $stats_cache_stations);

            $metrics = $this->cache->get($cache_name);
            if (empty($metrics)) {
                $metrics = $this->getMetrics($view_stations, $show_admin);
                $this->cache->set($cache_name, $metrics, 600);
            }
        }

        return $view->renderToResponse($response, 'frontend/index/index', [
            'stations' => ['stations' => $view_stations],
            'station_ids' => $station_ids,
            'show_admin' => $show_admin,
            'metrics' => $metrics,
            'notifications' => $notifications,
        ]);
    }

    /**
     * @param array $stationsToView
     * @param bool $showAdmin
     *
     * @return mixed[]
     */
    protected function getMetrics(array $stationsToView, bool $showAdmin = false): array
    {
        // Statistics by day.
        $threshold = CarbonImmutable::parse('-180 days');

        $stats = $this->em->createQuery(/** @lang DQL */ 'SELECT a.station_id, a.moment, a.number_avg, a.number_unique
            FROM App\Entity\Analytics a
            WHERE a.station_id IN (:stations)
            AND a.type = :type
            AND a.moment >= :threshold')
            ->setParameter('stations', $stationsToView)
            ->setParameter('type', Entity\Analytics::INTERVAL_DAILY)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $stationStats = [
            'average' => [],
            'unique' => [],
        ];

        $showAllStations = $showAdmin && count($stationsToView) > 1;

        foreach ($stats as $row) {
            $stationId = $row['station_id'];

            /** @var CarbonImmutable $moment */
            $moment = $row['moment'];

            $sortableKey = $moment->format('Y-m-d');
            $jsTimestamp = $moment->getTimestamp() * 1000;

            $average = round($row['number_avg'], 2);
            $unique = $row['number_unique'];

            $stationStats['average'][$stationId][$sortableKey] = [
                $jsTimestamp,
                $average,
            ];

            if (null !== $unique) {
                $stationStats['unique'][$stationId][$sortableKey] = [
                    $jsTimestamp,
                    $unique,
                ];
            }

            if ($showAllStations) {
                if (!isset($stationStats['average']['all'][$sortableKey])) {
                    $stationStats['average']['all'][$sortableKey] = [
                        $jsTimestamp,
                        0,
                    ];
                }
                $stationStats['average']['all'][$sortableKey][1] += $average;

                if (null !== $unique) {
                    if (!isset($stationStats['unique']['all'][$sortableKey])) {
                        $stationStats['unique']['all'][$sortableKey] = [
                            $jsTimestamp,
                            0,
                        ];
                    }
                    $stationStats['unique']['all'][$sortableKey][1] += $unique;
                }
            }
        }

        $stationsInMetric = [];
        if ($showAllStations) {
            $stationsInMetric['all'] = __('All Stations');
        }
        foreach ($stationsToView as $stationId => $station_info) {
            $stationsInMetric[$stationId] = $station_info['station']['name'];
        }

        $jsStats = [
            'average' => [
                'metrics' => [],
                'alt' => '',
            ],
            'unique' => [
                'metrics' => [],
                'alt' => '',
            ],
        ];

        foreach ($stationsInMetric as $stationId => $stationName) {
            foreach ($stationStats as $statKey => $statRows) {
                if (!isset($statRows[$stationId])) {
                    continue;
                }

                $series = [
                    'label' => $stationName,
                    'type' => 'line',
                    'fill' => false,
                    'data' => [],
                ];

                $jsStats[$statKey]['alt'] .= '<p>' . $stationName . '</p>';
                $jsStats[$statKey]['alt'] .= '<dl>';

                ksort($statRows[$stationId]);

                foreach ($statRows[$stationId] as $sortableKey => [$jsTimestamp, $value]) {
                    $series['data'][] = [
                        't' => $jsTimestamp,
                        'y' => $value,
                    ];

                    $jsStats[$statKey]['alt'] .= sprintf(
                        '<dt><time data-original="%s">%s</time></dt>',
                        $jsTimestamp,
                        $sortableKey
                    );
                    $jsStats[$statKey]['alt'] .= '<dd>' . $value . ' ' . __('Listeners') . '</dd>';
                }

                $jsStats[$statKey]['alt'] .= '</dl>';
                $jsStats[$statKey]['metrics'][] = $series;
            }
        }

        return $jsStats;
    }
}
