<?php
namespace Modules\Admin\Controllers;

class IndexController extends BaseController
{
    /**
     * Main display.
     */
    public function indexAction()
    {
        $stations = \Entity\Station::fetchAll();
        $this->view->stations = $stations;

        $internal_stations = array();
        foreach($stations as $station)
        {
            if ($station->category == "internal")
                $internal_stations[$station->id] = $station;
        }

        $metrics = \DF\Cache::get('admin_metrics');

        if (!$metrics)
        {
            // Statistics by day.
            $influx = $this->di->get('influx');
            $influx->setDatabase('pvlive_stations');

            $station_averages = array();
            $network_data = array(
                'PVL Network' => array(
                    'ranges' => array(),
                    'averages' => array(),
                ),
            );

            $daily_stats = $influx->query('SELECT * FROM /1d.*/', 'm');

            foreach($daily_stats as $stat_series => $stat_rows)
            {
                $series_split = explode('.', $stat_series);

                if ($series_split[1] == 'all')
                {
                    $network_name = 'PVL Network';
                    foreach($stat_rows as $stat_row)
                    {
                        $network_data[$network_name]['ranges'][] = array($stat_row['time'], $stat_row['min'], $stat_row['max']);
                        $network_data[$network_name]['averages'][] = array($stat_row['time'], $stat_row['value']);
                    }
                }
                else
                {
                    $station_id = $series_split[2];
                    foreach($stat_rows as $stat_row)
                    {
                        $station_averages[$station_id][] = array($stat_row['time'], $stat_row['value']);
                    }
                }
            }

            $network_metrics = array();
            foreach ($network_data as $network_name => $data_charts) {
                if (isset($data_charts['ranges'])) {
                    $metric_row = new \stdClass;
                    $metric_row->name = $network_name . ' Listener Range';
                    $metric_row->type = 'arearange';
                    $metric_row->data = $data_charts['ranges'];

                    $network_metrics[] = $metric_row;
                }

                if (isset($data_charts['averages'])) {
                    $metric_row = new \stdClass;
                    $metric_row->name = $network_name . ' Daily Average';
                    $metric_row->type = 'spline';
                    $metric_row->data = $data_charts['averages'];

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
                    $series_obj->data = $station_averages[$station_id];
                    $station_metrics[] = $series_obj;
                }
            }

            $network_metrics = json_encode($network_metrics);
            $station_metrics = json_encode($station_metrics);

            $metrics = array(
                'network'   => $network_metrics,
                'station'   => $station_metrics,
            );

            // \DF\Cache::save($network_metrics, 'admin_metrics', array(), 600);
        }

        $this->view->network_metrics = $metrics['network'];
        $this->view->station_metrics = $metrics['station'];

        // Synchronization statuses
        if ($this->acl->isAllowed('administer all'))
            $this->view->sync_times = \PVL\SyncManager::getSyncTimes();

        // PVLNode service stats.
        $this->view->pvlnode_stats = \PVL\Service\PvlNode::fetch();
    }

    public function syncAction()
    {
        $this->acl->checkPermission('administer all');

        $this->doNotRender();

        \PVL\Debug::setEchoMode(TRUE);
        \PVL\Debug::startTimer('sync_task');

        $type = $this->getParam('type', 'nowplaying');
        switch($type)
        {
            case "long":
                \PVL\SyncManager::syncLong();
            break;

            case "medium":
                \PVL\SyncManager::syncMedium();
            break;

            case "short":
                \PVL\SyncManager::syncShort();
            break;

            case "nowplaying":
            default:
                $segment = $this->getParam('segment', 1);
                define('NOWPLAYING_SEGMENT', $segment);

                \PVL\SyncManager::syncNowplaying(true);
            break;
        }

        \PVL\Debug::endTimer('sync_task');
        \PVL\Debug::log('Sync task complete. See log above.');
    }
}