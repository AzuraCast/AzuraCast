<?php

namespace Baseapp\Models;

/**
 * Role Model
 *
 * @package     base-app
 * @category    Model
 * @version     2.0
 */
class Roles extends \Phalcon\Mvc\Model
{

    /**
     * Role initialize
     *
     * @package     base-app
     * @version     2.0
     */
    public function initialize()
    {
        $this->hasMany('id', __NAMESPACE__ . '\RolesUsers', 'role_id', array(
            'alias' => 'RolesUsers',
        ));
    }

}
