<?php
namespace App\Controller\Frontend;

use App\Acl;
use App\Event\GetNotifications;
use Azura\Cache;
use App\Http\Router;
use App\Radio\Adapters;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use InfluxDB\Database;
use Psr\Http\Message\ResponseInterface;

class DashboardController
{
    /** @var EntityManager */
    protected $em;

    /** @var Acl */
    protected $acl;

    /** @var Cache */
    protected $cache;

    /** @var Database */
    protected $influx;

    /** @var Router */
    protected $router;

    /** @var Adapters */
    protected $adapter_manager;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param EntityManager $em
     * @param Acl $acl
     * @param Cache $cache
     * @param Database $influx
     * @param Adapters $adapter_manager
     * @param EventDispatcher $dispatcher
     *
     * @see \App\Provider\FrontendProvider
     */
    public function __construct(
        EntityManager $em,
        Acl $acl,
        Cache $cache,
        Database $influx,
        Adapters $adapter_manager,
        EventDispatcher $dispatcher
    ) {
        $this->em = $em;
        $this->acl = $acl;
        $this->cache = $cache;
        $this->influx = $influx;
        $this->adapter_manager = $adapter_manager;
        $this->dispatcher = $dispatcher;
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $view = $request->getView();
        $user = $request->getUser();
        $router = $request->getRouter();

        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        /** @var Entity\Station[] $stations */
        $stations = $station_repo->findAll();

        // Don't show stations the user can't manage.
        $stations = array_filter($stations, function($station) use ($user) {
            /** @var Entity\Station $station */
            return $station->isEnabled() &&
                $this->acl->userAllowed($user, Acl::STATION_VIEW, $station->getId());
        });

        if (empty($stations)) {
            return $view->renderToResponse($response, 'frontend/index/noaccess');
        }

        // Get administrator notifications.
        $notification_event = new GetNotifications($user);
        $this->dispatcher->dispatch(GetNotifications::NAME, $notification_event);

        $notifications = $notification_event->getNotifications();

        $view_stations = [];
        $station_ids = [];

        // Generate initial data for station dashboard view.
        foreach($stations as $row) {
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
                ]
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
                'public_url' => (string)$router->named('public:index', ['station' => $row->getShortName()]),
                'manage_url' => (string)$router->named('stations:index:index', ['station' => $row->getId()]),
                'stream_url' => (string)$frontend_adapter->getStreamUrl($row),
                'np' => $np,
            ];
            $station_ids[] = $row->getId();
        }

        // Detect current analytics level.

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $analytics_level = $settings_repo->getSetting(Entity\Settings::LISTENER_ANALYTICS, Entity\Analytics::LEVEL_ALL);

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            $metrics = null;
        } else {
            // Generate unique cache ID for stations.
            $stats_cache_stations = [];
            foreach ($stations as $station) {
                $stats_cache_stations[$station->getId()] = $station->getId();
            }

            $cache_name = 'homepage/metrics/' . implode(',', $stats_cache_stations);

            // $metrics = $this->cache->getOrSet($cache_name, function () use ($stations) {
            $metrics = function() use ($stations) {

                // Statistics by day.
                $station_averages = [];
                $network_data = [
                    'All Stations' => [
                        'ranges' => [],
                        'averages' => [],
                    ],
                ];

                // Query InfluxDB database.
                $resultset = $this->influx->query('SELECT * FROM "1d"./.*/ WHERE time > now() - 180d', [
                    'epoch' => 'ms',
                ]);

                $results_raw = $resultset->getSeries();
                $results = [];
                foreach ($results_raw as $serie) {
                    $points = [];
                    foreach ($serie['values'] as $point) {
                        $points[] = array_combine($serie['columns'], $point);
                    }

                    $results[$serie['name']] = $points;
                }

                foreach ($results as $stat_series => $stat_rows) {
                    $series_split = explode('.', $stat_series);

                    if ($series_split[1] === 'all') {
                        foreach ($stat_rows as $stat_row) {
                            // Add 12 hours to statistics so they always land inside the day they represent.
                            $stat_row['time'] = $stat_row['time'] + (60 * 60 * 12 * 1000);

                            $network_data['ranges'][$stat_row['time']] = [
                                $stat_row['time'],
                                $stat_row['min'],
                                $stat_row['max']
                            ];
                            $network_data['averages'][$stat_row['time']] = [
                                $stat_row['time'],
                                round($stat_row['value'], 2)
                            ];
                        }
                    } else {
                        $station_id = $series_split[1];
                        foreach ($stat_rows as $stat_row) {
                            // Add 12 hours to statistics so they always land inside the day they represent.
                            $stat_row['time'] = $stat_row['time'] + (60 * 60 * 12 * 1000);

                            $station_averages[$station_id][$stat_row['time']] = [
                                $stat_row['time'],
                                round($stat_row['value'], 2)
                            ];
                        }
                    }
                }

                $network_metrics = [];
                $network_metrics_alt = [];

                if (isset($network_data['averages'])) {
                    $metric_row = new \stdClass;
                    $metric_row->label = __('All Stations Daily Average');
                    $metric_row->type = 'line';
                    $metric_row->fill = false;

                    $network_metrics_alt[] = '<p>'.$metric_row->label.'</p>';
                    $network_metrics_alt[] = '<dl>';

                    ksort($network_data['averages']);

                    $series_data = [];
                    foreach($network_data['averages'] as $serie) {
                        $series_row = new \stdClass;
                        $series_row->t = $serie[0];
                        $series_row->y = $serie[1];
                        $series_data[] = $series_row;

                        $serie_date = gmdate('Y-m-d', $serie[0]/1000);
                        $network_metrics_alt[] = '<dt><time data-original="'.$serie[0].'">'.$serie_date.'</time></dt>';
                        $network_metrics_alt[] = '<dd>'.$serie[1].' '.__('Listeners').'</dd>';
                    }

                    $network_metrics_alt[] = '</dl>';

                    $metric_row->data = $series_data;
                    $network_metrics[] = $metric_row;
                }

                $station_metrics = [];
                $station_metrics_alt = [];

                foreach ($stations as $station) {
                    /** @var Entity\Station $station */
                    $station_id = $station->getId();

                    if (isset($station_averages[$station_id])) {
                        $series_obj = new \stdClass;
                        $series_obj->label = $station->getName();
                        $series_obj->type = 'line';
                        $series_obj->fill = false;

                        $station_metrics_alt[] = '<p>'.$series_obj->label.'</p>';
                        $station_metrics_alt[] = '<dl>';

                        ksort($station_averages[$station_id]);

                        $series_data = [];
                        foreach($station_averages[$station_id] as $serie) {
                            $series_row = new \stdClass;
                            $series_row->t = $serie[0];
                            $series_row->y = $serie[1];
                            $series_data[] = $series_row;

                            $serie_date = gmdate('Y-m-d', $serie[0]/1000);
                            $station_metrics_alt[] = '<dt><time data-original="'.$serie[0].'">'.$serie_date.'</time></dt>';
                            $station_metrics_alt[] = '<dd>'.$serie[1].' '.__('Listeners').'</dd>';
                        }

                        $station_metrics_alt[] = '</dl>';

                        $series_obj->data = $series_data;

                        $station_metrics[] = $series_obj;
                    }
                }

                return [
                    'network' => json_encode($network_metrics),
                    'network_alt' => implode('', $network_metrics_alt),
                    'station' => json_encode($station_metrics),
                    'station_alt' => implode('', $station_metrics_alt),
                ];

                // }, 600);
            };

            $metrics = $metrics();
        }

        return $view->renderToResponse($response, 'frontend/index/index', [
            'stations' => ['stations' => $view_stations],
            'station_ids' => $station_ids,
            'metrics' => $metrics,
            'notifications' => $notifications,
        ]);
    }
}
