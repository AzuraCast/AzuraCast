<?php
class Admin_IndexController extends \DF\Controller\Action
{
    public function permissions()
    {
        return \DF\Acl::getInstance()->isAllowed('view administration');
    }
    
    /**
     * Main display.
     */
    public function indexAction()
    {
        $this->view->stations = \Entity\Station::fetchAll();

        // Statistics by day.
        $daily_stats = $this->em->createQuery('SELECT a FROM Entity\Analytics a WHERE a.type = :type ORDER BY a.timestamp ASC')
            ->setParameter('type', 'day')
            ->getArrayResult();

        $pvl_ranges = array();
        $pvl_averages = array();
        $station_averages = array();

        foreach($daily_stats as $stat)
        {
            if (!$stat['station_id'])
            {
                $pvl_ranges[] = array($stat['timestamp']*1000, $stat['number_min'], $stat['number_max']);
                $pvl_averages[] = array($stat['timestamp']*1000, $stat['number_avg']);
            }
            else
            {
                $station_averages[$stat['station_id']][] = array($stat['timestamp']*1000, $stat['number_avg']);
            }
        }

        $this->view->pvl_ranges = json_encode($pvl_ranges);
        $this->view->pvl_averages = json_encode($pvl_averages);

        $stations = \Entity\Station::fetchArray();
        $station_metrics = array();

        foreach($stations as $station)
        {
            $station_id = $station['id'];

            $series_obj = new \stdClass;
            $series_obj->name = $station['name'];
            $series_obj->type = 'spline';
            $series_obj->data = $station_averages[$station_id];
            $station_metrics[] = $series_obj;
        }

        $this->view->station_metrics = json_encode($station_metrics);
    }
}