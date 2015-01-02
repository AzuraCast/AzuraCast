<?php

namespace Baseapp\Extension;

use Phalcon\Debug\Dump;

/**
 * SQL Listener
 *
 * @package     base-app
 * @category    Extension
 * @version     2.0
 */
class Listener
{

    /**
     * Display Real SQL Statement
     *
     * <code>
     * $eventsManager = new \Phalcon\Events\Manager();
     * $eventsManager->attach('db', new \Baseapp\Extension\Listener());
     * $this->db->setEventsManager($eventsManager);
     *
     * $query = $this->db->convertBoundParams('SELECT * FROM `users` WHERE `user_id` = :user_id:', array(':user_id' => 1));
     * $user = $this->db->fetchAll($query['sql'], \Phalcon\Db::FETCH_ASSOC, $query['params']);
     * </code>
     *
     * @package     base-app
     * @version     2.0
     */
    public function afterQuery($event, $connection, $params)
    {
        $statement = $connection->getSQLStatement();
        // If params is not empty
        if (!empty($params)) {
            // Check if params is assoc array
            if (array_keys($params) !== range(0, count($params) - 1)) {
                // Real SQL Statement
                $statement = str_replace(array_keys($params), array_values($params), $statement);
            } else {
                // Real SQL Statement after convertBoundParams
                foreach ($params as $param) {
                    $statement = preg_replace('/\?/', '"' . $param . '"', $statement, 1);
                }
            }
        }
        echo (new Dump())->all($statement);
    }

}
