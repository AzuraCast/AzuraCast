<?php
namespace Controller\Frontend;

use Entity;

class IndexController extends BaseController
{
    public function indexAction()
    {
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        // Don't show stations the user can't manage.
        $stations = array_filter($stations, function($station) {
            return $this->acl->isAllowed('view station management', $station->id);
        });

        if (empty($stations)) {
            return $this->render('controller::noaccess');
        }

        $view_stations = [];
        foreach($stations as $row) {
            /** @var Entity\Station $row */
            $view_stations[] = [
                'station' => $row,
                'short_name' => $row->getShortName(),
                'stream_url' => $row->getFrontendAdapter($this->di)->getStreamUrl(),
            ];
        }

        $this->view->stations = $view_stations;

        /** @var \App\Cache $cache */
        $cache = $this->di->get('cache');

        /** @var \InfluxDB\Database $influx */
        $influx = $this->di->get('influx');

        // Generate unique cache ID for stations.
        $stats_cache_stations = [];
        foreach($stations as $station) {
            $stats_cache_stations[$station->id] = $station->id;
        }

        $cache_name = 'homepage/metrics/'.md5(serialize($stats_cache_stations));

        $metrics = $cache->getOrSet($cache_name, function() use ($stations, $influx) {

            // Statistics by day.
            $station_averages = [];
            $network_data = [
                'All Stations' => [
                    'ranges' => [],
                    'averages' => [],
                ],
            ];

            // Query InfluxDB database.
            $resultset = $influx->query('SELECT * FROM "1d"./.*/ WHERE time > now() - 180d', [
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

                if ($series_split[1] == 'all') {
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
                    $metric_row->name = sprintf(_('%s Listener Range'), $network_name);
                    $metric_row->type = 'arearange';

                    ksort($data_charts['ranges']);
                    $metric_row->data = array_values($data_charts['ranges']);

                    $network_metrics[] = $metric_row;
                }

                if (isset($data_charts['averages'])) {
                    $metric_row = new \stdClass;
                    $metric_row->name = sprintf(_('%s Daily Average'), $network_name);
                    $metric_row->type = 'spline';

                    ksort($data_charts['averages']);
                    $metric_row->data = array_values($data_charts['averages']);

                    $network_metrics[] = $metric_row;
                }
            }

            $station_metrics = [];

            foreach ($stations as $station) {
                $station_id = $station['id'];

                if (isset($station_averages[$station_id])) {
                    $series_obj = new \stdClass;
                    $series_obj->name = $station['name'];
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

        $this->view->metrics = $metrics;
    }
}