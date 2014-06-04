<?php
namespace PVL;

use \Entity\Analytics;
use \Entity\Station;
use \Entity\Statistic;

class AnalyticsManager
{
	public static function run()
	{
		$em = \Zend_Registry::get('em');

		// Force all times to be UTC before continuing.
        date_default_timezone_set('UTC');

        $short_names = Station::getShortNameLookup();
        $current_date = date('Y-m-d');

        // Interval of seconds to use for "minute"-level statistics.
        $minute_interval = 600;
        $hour_interval = 3600;

        // Get the earliest date that statistics are available for.
        $earliest_date_raw = $em->createQuery('SELECT s.timestamp FROM Entity\Statistic s ORDER BY s.id ASC')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        if (!$earliest_date_raw)
            return;

        $earliest_timestamp = strtotime($earliest_date_raw);
        $earliest_date = date('Y-m-d', $earliest_timestamp);

        if ($earliest_date == $current_date)
            return;

        // Loop through all days.
        $start_timestamp = strtotime($earliest_date.' 00:00:00');
        for($i = $start_timestamp; $i < time(); $i += 86400)
        {
            set_time_limit(30);

            $delete_current_analytics = $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.timestamp BETWEEN :start AND :end')
                ->setParameter('start', $i)
                ->setParameter('end', $i+86400-1)
                ->execute();

            $current_date = date('Y-m-d', $i);
            $current_date_start = $current_date.' 00:00:00';
            $current_date_end = $current_date.' 23:59:59';

            $current_stats = $em->createQuery('SELECT s FROM Entity\Statistic s WHERE s.timestamp BETWEEN :start AND :end')
                ->setParameter('start', $current_date_start)
                ->setParameter('end', $current_date_end)
                ->getArrayResult();

            $totals = array();

            foreach($current_stats as $stat_row)
            {
                $stat_timestamp = $stat_row['timestamp']->getTimestamp();
                $stat_minute_interval = $stat_timestamp - ($stat_timestamp % $minute_interval);
                $stat_hour_interval = $stat_timestamp - ($stat_timestamp % $hour_interval);

                $totals['day']['all'][$i][] = $stat_row['total_overall'];
                $totals['hour']['all'][$stat_hour_interval][] = $stat_row['total_overall'];
                $totals['minute']['all'][$stat_minute_interval][] = $stat_row['total_overall'];

                foreach((array)$stat_row['total_stations'] as $shortcode => $total)
                {
                    $totals['day'][$shortcode][$i][] = $total;
                    $totals['hour'][$shortcode][$stat_hour_interval][] = $total;
                    $totals['minute'][$shortcode][$stat_minute_interval][] = $total;
                }
            }

            unset($current_stats); // Free up memory.

            foreach($totals as $total_type => $total_stations)
            {
                foreach($total_stations as $total_station => $total_periods)
                {
                    if ($total_station == 'all')
                        $station_id = NULL;
                    elseif (isset($short_names[$total_station]))
                        $station_id = $short_names[$total_station]['id'];
                    else
                        continue;

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

            if ($i < (time() - 86400*2))
            {
                $em->createQuery('DELETE FROM Entity\Statistic s WHERE s.timestamp BETWEEN :start AND :end')
                    ->setParameter('start', $current_date_start)
                    ->setParameter('end', $current_date_end)
                    ->execute();
            }
        }
    }
}