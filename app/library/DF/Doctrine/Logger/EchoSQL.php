<?php
namespace DF\Doctrine\Logger;

class EchoSQL extends \Doctrine\DBAL\Logging\EchoSQLLogger
{
    public function startQuery($sql, array $params = null, array $types = null)
    {
        echo $sql.PHP_EOL;

        if ($params) {
            var_dump($params);
        }

        if ($types) {
            var_dump($types);
        }
        
        $memory = memory_get_usage();
        $mb = round($memory / (1024 * 1024), 4).'M';
        
        echo $mb.'/'.ini_get('memory_limit').PHP_EOL;
        
    }
    
    public function stopQuery()
    {}
}