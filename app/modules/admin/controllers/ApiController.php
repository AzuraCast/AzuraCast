<?php
namespace Modules\Admin\Controllers;

class ApiController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        $influx = $this->di->get('influx');
        $influx->setDatabase('analytics');

        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $stats = \App\Cache::get('admin_api_calls');

        if (!$stats)
        {
            $threshold = time() - (86400 * 2.5);

            $seconds_in_threshold = time() - $threshold;
            $minutes_in_threshold = round($seconds_in_threshold / 60);

            $stats = array(
                'speed_by_function' => array(),
                'calls_by_function' => array(),
                'calls_by_client' => array(),
                'calls_by_useragent' => array(),
                'calls_by_ip' => array(),
                'calls_by_hour' => array(),
            );

            // Speed and Calls by Function
            try
            {
                $stats_by_func = $influx->query('SELECT count(value) AS total_calls, mean(requesttime) AS request_time, controller FROM api_calls GROUP BY controller', 's');
                $stats_by_func = array_pop($stats_by_func);
            }
            catch(\Exception $e)
            {
                $stats_by_func = array();
            }

            $total_calls = 0;

            foreach($stats_by_func as $func_row)
            {
                $func = $func_row['controller'];

                $total_calls += $func_row['total_calls'];

                $stats['speed_by_function'][$func] = round($func_row['request_time'] * 1000, 2);
                $stats['calls_by_function'][$func] = $func_row['total_calls'];
            }

            // Calls per client
            try
            {
                $stats_by_client = $influx->query('SELECT count(value) AS num_calls, client FROM api_calls GROUP BY client');
                $stats_by_client = array_pop($stats_by_client);
            }
            catch(\Exception $e)
            {
                $stats_by_client = array();
            }

            foreach($stats_by_client as $row)
            {
                $stats['calls_by_client'][$row['client']] = $row['num_calls'];
            }

            // Calls per user-agent
            try
            {
                $stats_by_ua = $influx->query('SELECT count(value) AS num_calls, useragent FROM api_calls GROUP BY useragent');
                $stats_by_ua = array_pop($stats_by_ua);
            }
            catch(\Exception $e)
            {
                $stats_by_ua = array();
            }

            foreach($stats_by_ua as $row)
            {
                $stats['calls_by_useragent'][$row['useragent']] = $row['num_calls'];
            }

            // Calls per IP
            try
            {
                $stats_by_ip = $influx->query('SELECT count(value) AS num_calls, ip FROM api_calls GROUP BY ip');
                $stats_by_ip = array_pop($stats_by_ip);
            }
            catch(\Exception $e)
            {
                $stats_by_ip = array();
            }

            foreach($stats_by_ip as $row)
            {
                $stats['calls_by_ip'][$row['ip']] = $row['num_calls'];
            }

            /*
            // Calls by hour

            */

            foreach($stats as $stat_category => &$stat_items)
            {
                arsort($stat_items);
                $stat_items = array_slice($stat_items, 0, 10);

                foreach($stat_items as $stat_key => &$stat_value)
                {
                    $stat_value = array(
                        'total'     => $stat_value,
                        'percentage'   => round(($stat_value / $total_calls) * 100, 2),
                    );
                }
            }

            $stats['meta'] = array(
                'threshold' => $threshold,
                'total_calls' => $total_calls,
                'calls_per_minute' => round($total_calls / $minutes_in_threshold, 3),
            );

            \App\Cache::save($stats, 'admin_api_calls', array(), 300);
        }

        $this->view->statistics = $stats;
    }
}