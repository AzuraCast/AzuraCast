<?php

namespace Baseapp\Models;

/**
 * User roles Model
 *
 * @package     base-app
 * @category    Model
 * @version     2.0
 */
class RolesUsers extends \Phalcon\Mvc\Model
{

    /**
     * Roles Users initialize
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
        $this->belongsTo('role_id', __NAMESPACE__ . '\Roles', 'id', array(
            'alias' => 'Role',
            'foreignKey' => true
        ));
    }

}
