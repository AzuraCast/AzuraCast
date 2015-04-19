<?php
namespace Modules\Frontend\Controllers;

use \Entity\Song;

class UtilController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        \PVL\Debug::setEchoMode();

        $influx = $this->di->get('influx');
        $influx->setDatabase('pvlive_stations');

        $old_analytics = $this->em->createQuery('SELECT a FROM Entity\Analytics a WHERE a.type = :type')
            ->setParameter('type', 'day')
            ->getArrayResult();

        foreach($old_analytics as $row)
        {
            if ($row['station_id'])
                $series = 'station.'.$row['station_id'];
            else
                $series = 'all';

            $influx->insert('1d.'.$series.'.listeners', [
                'time'  => $row['timestamp'],
                'value' => $row['number_avg'],
                'min' => $row['number_min'],
                'max' => $row['number_max'],
            ], 's');
        }

        //\PVL\NotificationManager::run();

        \PVL\Debug::log('Donezo!');
    }
}