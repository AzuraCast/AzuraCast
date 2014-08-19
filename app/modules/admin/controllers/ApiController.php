<?php
class Admin_ApiController extends \PVL\Controller\Action\Admin
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $stats = \DF\Cache::get('admin_api_calls');

        if (!$stats)
        {
            $threshold = strtotime('-1 day');

            $seconds_in_threshold = time() - $threshold;
            $minutes_in_threshold = round($seconds_in_threshold / 60);

            $api_calls = $this->em->createQuery('SELECT ac FROM Entity\ApiCall ac WHERE ac.timestamp >= :threshold ORDER BY ac.id ASC')
                ->setParameter('threshold', $threshold);

            $raw_stats = array(
                'speed_by_function' => array(),
                'calls_by_function' => array(),
                'calls_by_client' => array(),
                'calls_by_useragent' => array(),
                'calls_by_ip' => array(),
                'calls_by_hour' => array(),
            );

            $num_calls = 0;

            $api_iterator = $api_calls->iterate();

            // Organize raw statistics.
            foreach ($api_iterator as $row_raw)
            {
                $num_calls++;

                $row = $row_raw[0];
                $func = $row['controller'] . '/' . $row['action'];

                // Speed by function
                if (!isset($raw_stats['speed_by_function'][$func]))
                    $raw_stats['speed_by_function'][$func] = array();

                $raw_stats['speed_by_function'][$func][] = $row['requesttime'];

                // Calls by function
                if (!isset($raw_stats['calls_by_function'][$func]))
                    $raw_stats['calls_by_function'][$func] = 0;

                $raw_stats['calls_by_function'][$func]++;

                // Calls by client
                if (!isset($raw_stats['calls_by_client'][$row['client']]))
                    $raw_stats['calls_by_client'][$row['client']] = 0;

                $raw_stats['calls_by_client'][$row['client']]++;

                // Calls by user-agent
                if (!isset($raw_stats['calls_by_useragent'][$row['useragent']]))
                    $raw_stats['calls_by_useragent'][$row['useragent']] = 0;

                $raw_stats['calls_by_useragent'][$row['useragent']]++;

                // Calls by IP address
                if (!isset($raw_stats['calls_by_ip'][$row['ip']]))
                    $raw_stats['calls_by_ip'][$row['ip']] = 0;

                $raw_stats['calls_by_ip'][$row['ip']]++;

                // Calls by hour
                $hour = date('G', $row['timestamp']);

                if (!isset($raw_stats['calls_by_hour'][$hour]))
                    $raw_stats['calls_by_hour'][$hour] = 0;

                $raw_stats['calls_by_hour'][$hour]++;

                $this->em->detach($row);
            }

            // Free up memory.
            $this->em->clear();

            // Average speed by function.
            $new_speed_by_func = array();

            foreach ($raw_stats['speed_by_function'] as $func_name => $func_times)
            {
                $avg_speed = round(array_sum($func_times) / count($func_times), 5);
                $speed_in_ms = $avg_speed * 1000;

                $new_speed_by_func[$func_name] = $speed_in_ms;
            }

            $raw_stats['speed_by_function'] = $new_speed_by_func;

            // Convert 'calls by function' into 'calls by minute'
            $calls_by_minute = array();

            foreach ($raw_stats['calls_by_function'] as $func_name => $func_totals)
            {
                $cpm = round($func_totals / $minutes_in_threshold, 3);
                $calls_by_minute[$func_name] = $cpm;
            }

            $raw_stats['calls_by_function'] = $calls_by_minute;

            // Group and arrange stats into a visual format.
            $stats = array();

            foreach ($raw_stats as $stat_type => $stat_values)
            {
                $stat = array();

                if ($stat_type == 'calls_by_hour')
                {
                    ksort($stat_values);
                } else
                {
                    arsort($stat_values);

                    if (!in_array($stat_type, array('calls_by_function', 'speed_by_function')))
                    {
                        $stat_values = array_slice($stat_values, 0, 30, TRUE);
                    }
                }

                foreach ($stat_values as $stat_key => $calls)
                {
                    if ($stat_type == 'speed_by_function')
                    {
                        $stat[$stat_key] = array('total' => $calls);
                    } elseif ($stat_type == 'calls_by_function')
                    {
                        $all_calls_per_hour = array_sum($stat_values);
                        $percentage = round(($calls / $all_calls_per_hour) * 100);

                        $stat[$stat_key] = array(
                            'total' => $calls,
                            'percentage' => $percentage . '%',
                        );
                    } else
                    {
                        $percentage = round(($calls / $num_calls) * 100);
                        $stat[$stat_key] = array(
                            'total' => $calls,
                            'percentage' => $percentage . '%',
                        );
                    }
                }

                $stats[$stat_type] = $stat;
            }

            $stats['meta'] = array(
                'threshold' => $threshold,
                'total_calls' => $num_calls,
                'calls_per_minute' => round($num_calls / $minutes_in_threshold, 3),
            );

            \DF\Cache::save($stats, 'admin_api_calls', array(), 300);
        }

        $this->view->statistics = $stats;
    }
}