<?php
namespace Modules\Frontend\Controllers;

use Entity\Station;
use Entity\Settings;

class IndexController extends BaseController
{
    public function indexAction()
    {
        // Inject all stations.
        $stations = \Entity\Station::fetchAll();
        $this->view->stations = $stations;

        // Pull cached statistic charts if available.
        $cache = $this->di->get('cache');
        $metrics = $cache->get('admin_metrics');

        if (!$metrics)
        {
            // Statistics by day.
            $influx = $this->di->get('influx');
            $station_averages = array();
            $network_data = array(
                'All Stations' => array(
                    'ranges' => array(),
                    'averages' => array(),
                ),
            );

            $daily_stats = $influx->query('SELECT * FROM /1d.*/ WHERE time > now() - 180d', 'm');

            foreach($daily_stats as $stat_series => $stat_rows)
            {
                $series_split = explode('.', $stat_series);

                if ($series_split[1] == 'all')
                {
                    $network_name = 'All Stations';
                    foreach($stat_rows as $stat_row)
                    {
                        $network_data[$network_name]['ranges'][$stat_row['time']] = array($stat_row['time'], $stat_row['min'], $stat_row['max']);
                        $network_data[$network_name]['averages'][$stat_row['time']] = array($stat_row['time'], round($stat_row['value'], 2));
                    }
                }
                else
                {
                    $station_id = $series_split[2];
                    foreach($stat_rows as $stat_row)
                    {
                        $station_averages[$station_id][$stat_row['time']] = array($stat_row['time'], round($stat_row['value'], 2));
                    }
                }
            }

            $network_metrics = array();
            foreach ($network_data as $network_name => $data_charts) {
                if (isset($data_charts['ranges'])) {
                    $metric_row = new \stdClass;
                    $metric_row->name = $network_name . ' Listener Range';
                    $metric_row->type = 'arearange';

                    ksort($data_charts['ranges']);
                    $metric_row->data = array_values($data_charts['ranges']);

                    $network_metrics[] = $metric_row;
                }

                if (isset($data_charts['averages'])) {
                    $metric_row = new \stdClass;
                    $metric_row->name = $network_name . ' Daily Average';
                    $metric_row->type = 'spline';

                    ksort($data_charts['averages']);
                    $metric_row->data = array_values($data_charts['averages']);

                    $network_metrics[] = $metric_row;
                }
            }

            $station_metrics = array();

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

            $metrics = array(
                'network'   => json_encode($network_metrics),
                'station'   => json_encode($station_metrics),
            );

            $cache->save($metrics, 'admin_metrics', array(), 600);
        }

        $this->view->metrics = $metrics;
    }
}