<?php
namespace PVL;

use \Entity\Analytics;
use \Entity\Station;
use \Entity\Statistic;

class AnalyticsManager
{
    public static function addTracking($url, $utm_data = array())
    {
        $utm_defaults = array(
            'source'        => 'pvlive',
            'medium'        => 'direct',
            'campaign'      => 'pvlive',
            'term'          => NULL,
            'content'       => NULL,
        );

        $utm_data = array_merge($utm_defaults, $utm_data);

        $url_parts = Utilities::parseUrl($url);

        $url_params = (array)$url_parts['query_arr'];

        foreach($utm_data as $utm_key => $utm_val)
        {
            if (!empty($utm_val))
                $url_params['utm_' . $utm_key] = $utm_val;
        }

        $url_parts['query'] = http_build_query($url_params);
        return Utilities::buildUrl($url_parts);
    }

    public static function run()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        // Clear out any non-daily statistics.
        $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.type != :type')
            ->setParameter('type', 'day')
            ->execute();

        // Pull statistics in from influx.
        $influx = $di->get('influx');
        $daily_stats = $influx->setDatabase('pvlive_stations')->query('SELECT * FROM /1d.*/ WHERE time > now() - 14d', 's');

        $new_records = array();
        $earliest_timestamp = time();

        foreach($daily_stats as $stat_series => $stat_rows)
        {
            $series_split = explode('.', $stat_series);
            $station_id = ($series_split[1] == 'all') ? NULL : $series_split[2];

            foreach($stat_rows as $stat_row)
            {
                if ($stat_row['time'] < $earliest_timestamp)
                    $earliest_timestamp = $stat_row['time'];

                $new_records[] = array(
                    'station_id' => $station_id,
                    'type' => 'day',
                    'timestamp' => $stat_row['time'],
                    'number_min' => (int)$stat_row['min'],
                    'number_max' => (int)$stat_row['max'],
                    'number_avg' => round($stat_row['value']),
                );
            }
        }

        $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.timestamp >= :earliest')
            ->setParameter('earliest', $earliest_timestamp)
            ->execute();

        foreach($new_records as $new_record)
        {
            $row = new \Entity\Analytics;
            $row->fromArray($new_record);
            $em->persist($row);
        }

        $em->flush();
    }

    /* Legacy analytics manager script.
    public static function runOld()
    {
        set_time_limit(60*50);

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
                ->iterate();

            $totals = array();

            foreach($current_stats as $stat_iterated_row)
            {
                $stat_row = $stat_iterated_row[0];
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

                $em->detach($stat_row);
                unset($stat_row);
            }

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
    */
}