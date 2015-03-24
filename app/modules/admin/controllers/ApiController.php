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
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $stats = \DF\Cache::get('admin_api_calls');

        if (!$stats)
        {
            $threshold = strtotime('-1 day');

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
            $stats_by_func = $this->em->createQuery('SELECT CONCAT(ac.controller, \'/\', ac.action) AS func_name, COUNT(ac) AS total_calls, AVG(ac.requesttime) AS request_time FROM Entity\ApiCall ac WHERE ac.timestamp >= :threshold GROUP BY func_name')
                ->setParameter('threshold', $threshold)
                ->getArrayResult();

            $total_calls = 0;

            foreach($stats_by_func as $func_row)
            {
                $func = $func_row['func_name'];

                $total_calls += $func_row['total_calls'];

                $stats['speed_by_function'][$func] = round($func_row['request_time'] * 1000, 2);
                $stats['calls_by_function'][$func] = $func_row['total_calls'];
            }

            // Calls per client
            $stats_by_client = $this->em->createQuery('SELECT ac.client, COUNT(ac.id) AS num_calls FROM \Entity\ApiCall ac WHERE ac.timestamp >= :threshold GROUP BY ac.client ORDER BY num_calls DESC')
                ->setParameter('threshold', $threshold)
                ->setMaxResults(10)
                ->getArrayResult();

            foreach($stats_by_client as $row)
            {
                $stats['calls_by_client'][$row['client']] = $row['num_calls'];
            }

            // Calls per user-agent
            $stats_by_ua = $this->em->createQuery('SELECT ac.useragent, COUNT(ac.id) AS num_calls FROM \Entity\ApiCall ac WHERE ac.timestamp >= :threshold GROUP BY ac.useragent ORDER BY num_calls DESC')
                ->setParameter('threshold', $threshold)
                ->setMaxResults(10)
                ->getArrayResult();

            foreach($stats_by_ua as $row)
            {
                $stats['calls_by_useragent'][$row['useragent']] = $row['num_calls'];
            }

            // Calls per IP
            $stats_by_ip = $this->em->createQuery('SELECT ac.ip, COUNT(ac.id) AS num_calls FROM \Entity\ApiCall ac WHERE ac.timestamp >= :threshold GROUP BY ac.ip ORDER BY num_calls DESC')
                ->setParameter('threshold', $threshold)
                ->setMaxResults(10)
                ->getArrayResult();

            foreach($stats_by_ip as $row)
            {
                $stats['calls_by_ip'][$row['ip']] = $row['num_calls'];
            }

            // TODO: Calls by hour

            /*
            // Calls by hour
            $hour = date('G', $row['timestamp']);

            if (!isset($raw_stats['calls_by_hour'][$hour]))
                $raw_stats['calls_by_hour'][$hour] = 0;

            $raw_stats['calls_by_hour'][$hour]++;
            */

            arsort($stats['calls_by_function']);
            arsort($stats['speed_by_function']);

            foreach($stats as $stat_category => &$stat_items)
            {
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

            \DF\Cache::save($stats, 'admin_api_calls', array(), 300);
        }

        $this->view->statistics = $stats;
    }
}