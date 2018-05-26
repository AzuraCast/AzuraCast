<?php
namespace Controller\Frontend;

use App\Cache;
use App\Url;
use AzuraCast\Acl\StationAcl;
use AzuraCast\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;
use InfluxDB\Database;

class DashboardController
{
    /** @var EntityManager */
    protected $em;

    /** @var StationAcl */
    protected $acl;

    /** @var Cache */
    protected $cache;

    /** @var Database */
    protected $influx;

    /** @var Url */
    protected $url;

    /** @var Adapters */
    protected $adapter_manager;

    /**
     * IndexController constructor.
     * @param EntityManager $em
     * @param StationAcl $acl
     * @param Cache $cache
     * @param Database $influx
     * @param Adapters $adapter_manager
     */
    public function __construct(EntityManager $em, StationAcl $acl, Cache $cache, Database $influx, Adapters $adapter_manager, Url $url)
    {
        $this->em = $em;
        $this->acl = $acl;
        $this->cache = $cache;
        $this->influx = $influx;
        $this->adapter_manager = $adapter_manager;
        $this->url = $url;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        /** @var Entity\Station[] $stations */
        $stations = $station_repo->findAll();

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        // Don't show stations the user can't manage.
        $stations = array_filter($stations, function($station) use ($user) {
            /** @var Entity\Station $station */
            return $station->isEnabled() &&
                $this->acl->userAllowed($user, 'view station management', $station->getId());
        });

        if (empty($stations)) {
            return $view->renderToResponse($response, 'frontend/index/noaccess');
        }

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
                'public_url' => $this->url->named('public:index', ['station' => $row->getShortName()]),
                'manage_url' => $this->url->named('stations:index:index', ['station' => $row->getId()]),
                'stream_url' => $frontend_adapter->getStreamUrl(),
                'np' => $np,
            ];
            $station_ids[] = $row->getId();
        }

        // Detect current analytics level.

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $analytics_level = $settings_repo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            $metrics = null;
        } else {
            // Generate unique cache ID for stations.
            $stats_cache_stations = [];
            foreach ($stations as $station) {
                $stats_cache_stations[$station->getId()] = $station->getId();
            }

            $cache_name = 'homepage/metrics/' . implode(',', $stats_cache_stations);

            $metrics = $this->cache->getOrSet($cache_name, function () use ($stations) {

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
                        $network_name = 'All Stations';
                        foreach ($stat_rows as $stat_row) {
                            // Add 12 hours to statistics so they always land inside the day they represent.
                            $stat_row['time'] = $stat_row['time'] + (60 * 60 * 12 * 1000);

                            $network_data[$network_name]['ranges'][$stat_row['time']] = [
                                $stat_row['time'],
                                $stat_row['min'],
                                $stat_row['max']
                            ];
                            $network_data[$network_name]['averages'][$stat_row['time']] = [
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
                foreach ($network_data as $network_name => $data_charts) {
                    if (isset($data_charts['ranges'])) {
                        $metric_row = new \stdClass;
                        $metric_row->name = __('%s Listener Range', $network_name);
                        $metric_row->type = 'arearange';

                        ksort($data_charts['ranges']);
                        $metric_row->data = array_values($data_charts['ranges']);

                        $network_metrics[] = $metric_row;
                    }

                    if (isset($data_charts['averages'])) {
                        $metric_row = new \stdClass;
                        $metric_row->name = __('%s Daily Average', $network_name);
                        $metric_row->type = 'spline';

                        ksort($data_charts['averages']);
                        $metric_row->data = array_values($data_charts['averages']);

                        $network_metrics[] = $metric_row;
                    }
                }

                $station_metrics = [];

                foreach ($stations as $station) {
                    /** @var Entity\Station $station */
                    $station_id = $station->getId();

                    if (isset($station_averages[$station_id])) {
                        $series_obj = new \stdClass;
                        $series_obj->name = $station->getName();
                        $series_obj->type = 'spline';

                        ksort($station_averages[$station_id]);
                        $series_obj->data = array_values($station_averages[$station_id]);
                        $station_metrics[] = $series_obj;
                    }
                }

                return [
                    'network' => json_encode($network_metrics),
                    'station' => json_encode($station_metrics),
                ];

            }, 600);
        }

        return $view->renderToResponse($response, 'frontend/index/index', [
            'stations' => ['stations' => $view_stations],
            'station_ids' => $station_ids,
            'metrics' => $metrics,
        ]);
    }
}