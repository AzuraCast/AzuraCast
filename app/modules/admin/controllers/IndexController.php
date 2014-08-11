<?php
class Admin_IndexController extends \PVL\Controller\Action\Admin
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

        // Statistics by day.
        $daily_stats = $this->em->createQuery('SELECT a FROM Entity\Analytics a WHERE a.type = :type ORDER BY a.timestamp ASC')
            ->setParameter('type', 'day')
            ->getArrayResult();

        $station_averages = array();
        $network_data = array(
            'PVL Network' => array(
                'ranges' => array(),
                'averages' => array(),
            ),
        );

        foreach($daily_stats as $stat)
        {
            if (!$stat['station_id'])
            {
                $network_name = 'PVL Network';
                $network_data[$network_name]['ranges'][] = array($stat['timestamp']*1000, $stat['number_min'], $stat['number_max']);
                $network_data[$network_name]['averages'][] = array($stat['timestamp']*1000, $stat['number_avg']);
            }
            elseif (isset($internal_stations[$stat['station_id']]))
            {
                $network_name = $internal_stations[$stat['station_id']]['name'];
                $network_data[$network_name]['ranges'][] = array($stat['timestamp']*1000, $stat['number_min'], $stat['number_max']);
                $network_data[$network_name]['averages'][] = array($stat['timestamp']*1000, $stat['number_avg']);
            }
            else
            {
                $station_averages[$stat['station_id']][] = array($stat['timestamp']*1000, $stat['number_avg']);
            }
        }

        $network_metrics = array();
        foreach($network_data as $network_name => $data_charts)
        {
            if (isset($data_charts['ranges']))
            {
                $metric_row = new \stdClass;
                $metric_row->name = $network_name.' Listener Range';
                $metric_row->type = 'arearange';
                $metric_row->data = $data_charts['ranges'];

                $network_metrics[] = $metric_row;
            }

            if (isset($data_charts['averages']))
            {
                $metric_row = new \stdClass;
                $metric_row->name = $network_name.' Daily Average';
                $metric_row->type = 'spline';
                $metric_row->data = $data_charts['averages'];

                $network_metrics[] = $metric_row;
            }
        }

        $station_metrics = array();

        foreach($stations as $station)
        {
            $station_id = $station['id'];

            if (isset($station_averages[$station_id]))
            {
                $series_obj = new \stdClass;
                $series_obj->name = $station['name'];
                $series_obj->type = 'spline';
                $series_obj->data = $station_averages[$station_id];
                $station_metrics[] = $series_obj;
            }
        }

        $this->view->network_metrics = json_encode($network_metrics);
        $this->view->station_metrics = json_encode($station_metrics);

        // Synchronization statuses
        if ($this->acl->isAllowed('administer all'))
            $this->view->sync_times = \PVL\SyncManager::getSyncTimes();
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
                \PVL\SyncManager::syncNowplaying();
            break;
        }

        \PVL\Debug::endTimer('sync_task');
        \PVL\Debug::log('Sync task complete. See log above.');
    }
}