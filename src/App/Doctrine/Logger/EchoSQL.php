<?php
namespace App\Doctrine\Logger;

use App\Debug;

class EchoSQL extends \Doctrine\DBAL\Logging\EchoSQLLogger
{
    public function startQuery($sql, array $params = null, array $types = null)
    {
        static $is_started;

        if (!$is_started) {
            Debug::setEchoMode();
            $is_started = true;
        }

        Debug::log($sql);

        if ($params) {
            Debug::print_r($params);
        }

        if ($types) {
            Debug::print_r($types);
        }

        $memory = memory_get_usage();
        $mb = round($memory / (1024 * 1024), 4) . 'M';

        Debug::log('Memory: ' . $mb . '/' . ini_get('memory_limit'));
    }

    public function stopQuery()
    {
    }
}