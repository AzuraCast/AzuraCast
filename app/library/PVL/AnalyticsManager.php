<?php
namespace PVL;

use \Entity\Analytics;
use \Entity\Station;
use \Entity\Statistic;

class AnalyticsManager
{
    public static function run()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        // Force all times to be UTC before continuing.
        date_default_timezone_set('UTC');

        $current_date = gmdate('Y-m-d');

        // Interval of seconds to use for "minute"-level statistics.
        $minute_interval = 600;
        $hour_interval = 3600;

        // Get the earliest date that statistics are available for.
        try
        {
            $earliest_timestamp = $em->createQuery('SELECT a.timestamp FROM Entity\Analytics a WHERE a.type = :type ORDER BY a.timestamp ASC')
                ->setParameter('type', 'second')
                ->setMaxResults(1)
                ->getSingleScalarResult();
        }
        catch(\Exception $e) { return false; }

        $earliest_date = gmdate('Y-m-d', $earliest_timestamp);

        if ($earliest_date == $current_date)
            return false;

        // Loop through all days.
        $start_timestamp = strtotime($earliest_date.' 00:00:00');
        for($i = $start_timestamp; $i < time(); $i += 86400)
        {
            set_time_limit(30);

            $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.type != :type AND a.timestamp BETWEEN :start AND :end')
                ->setParameter('type', 'second')
                ->setParameter('start', $i)
                ->setParameter('end', $i+86400-1)
                ->execute();

            $current_date = gmdate('Y-m-d', $i);
            $current_date_start = strtotime($current_date.' 00:00:00');
            $current_date_end = strtotime($current_date.' 23:59:59');

            $current_stats = $em->createQuery('SELECT a FROM Entity\Analytics a WHERE a.type = :type AND a.timestamp BETWEEN :start AND :end')
                ->setParameter('type', 'second')
                ->setParameter('start', $current_date_start)
                ->setParameter('end', $current_date_end)
                ->getArrayResult();

            $totals = array();

            foreach($current_stats as $stat_row)
            {
                $total = $stat_row['number_avg'];

                $stat_timestamp = $stat_row['timestamp'];
                $stat_minute_interval = $stat_timestamp - ($stat_timestamp % $minute_interval);
                $stat_hour_interval = $stat_timestamp - ($stat_timestamp % $hour_interval);

                if ($stat_row['station_id'])
                {
                    $station_id = $stat_row['station_id'];

                    $totals['day'][$station_id][$i][] = $total;
                    $totals['hour'][$station_id][$stat_hour_interval][] = $total;
                    $totals['minute'][$station_id][$stat_minute_interval][] = $total;
                }
                else
                {
                    $totals['day']['all'][$i][] = $total;
                    $totals['hour']['all'][$stat_hour_interval][] = $total;
                    $totals['minute']['all'][$stat_minute_interval][] = $total;
                }
            }

            unset($current_stats); // Free up memory.

            foreach($totals as $total_type => $total_stations)
            {
                foreach($total_stations as $total_station => $total_periods)
                {
                    if ($total_station == 'all')
                        $station_id = NULL;
                    else
                        $station_id = $total_station;

                    foreach($total_periods as $total_period => $total_contents)
                    {
                        $record = new Analytics;
                        $record->fromArray(array(
                            'station_id' => $station_id,
                            'type' => $total_type,
                            'timestamp' => $total_period,
                        ));
                        $record->calculateFromArray($total_contents);
                        $em->persist($record);
                    }
                }

                $em->flush();
                $em->clear();
            }
        }

        $cleanup_thresholds = array(
            'second'        => strtotime('Yesterday 00:00:00'),
            'minute'        => strtotime('-1 month'),
            'hour'          => strtotime('-6 months'),
        );

        foreach($cleanup_thresholds as $cleanup_type => $cleanup_timestamp)
        {
            $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.type = :type AND a.timestamp < :timestamp')
                ->setParameter('type', $cleanup_type)
                ->setParameter('timestamp', $cleanup_timestamp)
                ->execute();
        }
    }
}