<?php

namespace Baseapp\Models;

/**
 * Token Model
 *
 * @package     base-app
 * @category    Model
 * @version     2.0
 */
class Tokens extends \Phalcon\Mvc\Model
{

    /**
     * Set correct db table
     *
     * @package     base-app
     * @version     2.0
     */
    public function getSource()
    {
        return 'user_tokens';
    }

    /**
     * Token initialize
     *
     * @package     base-app
     * @version     2.0
     */
    public function initialize()
    {
        $this->belongsTo('user_id', __NAMESPACE__ . '\Users', 'id', array(
            'alias' => 'User',
            'foreignKey' => true
        ));

        // Do garbage collection
        if (mt_rand(1, 100) === 1) {
            $this->delete_expired();
        }

        // This object has expired
        if (property_exists($this, 'expires') && $this->expires < time()) {
            $this->delete();
        }
    }

    /**
     * Deletes all expired tokens
     *
     * @package     base-app
     * @version     2.0
     */
    public function delete_expired()
    {
        $this->getDI()->getShared('db')->execute('DELETE FROM `user_tokens` WHERE `expires` < :time', array(':time' => time()));
    }

}
