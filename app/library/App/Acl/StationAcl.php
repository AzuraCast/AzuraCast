<?php
/**
 * Access Control List (ACL) manager
 */

namespace App\Acl;

use \Entity\User;
use \Entity\Role;
use \Entity\Station;

class StationAcl extends \App\Acl
{
    /**
     * Check if a specified User entity is allowed to perform an action (or array of actions).
     *
     * @param string|array $action
     * @param User|null $user
     * @return mixed
     */
    public function userAllowed($action, User $user = null, $station_id = null)
    {
        // Make all actions lower-case and sort alphabetically (so memoization returns the same result).
        $action = array_map('strtolower', (array)$action);
        asort($action);

        $memoize_text = serialize($action);
        $memoize = ($station_id !== null) ? md5($memoize_text.'_'.$station_id) : md5($memoize_text);

        $user_id = ($user instanceof User) ? $user->id : 'anonymous';

        if( !isset($this->_cache[$user_id][$memoize]) )
        {
            if($user instanceof User)
            {
                if(!isset($this->_roles[$user_id]))
                {
                    $this->_roles[$user_id] = array();

                    if (count($user->roles) > 0)
                    {
                        foreach($user->roles as $role)
                            $this->_roles[$user_id][] = $role->id;
                    }
                }

                $this->_cache[$user_id][$memoize] = $this->roleAllowed($this->_roles[$user_id], $action, $station_id);
            }
            else
            {
                $this->_cache[$user_id][$memoize] = $this->roleAllowed(array('Unauthenticated'), $action, $station_id);
            }
        }

        return $this->_cache[$user_id][$memoize];
    }

    /**
     * Check if the currently logged-in user can perform a specified action.
     *
     * @param string $action
     * @return bool|mixed
     */
    public function isAllowed($action, $station_id = null)
    {
        $user = $this->_auth->getLoggedInUser();
        $is_logged_in = ($user instanceof User);

        if ($action == "is logged in")
            return ($is_logged_in);
        elseif ($action == "is not logged in")
            return (!$is_logged_in);
        elseif ($is_logged_in)
            return $this->userAllowed($action, $user, $station_id);
        else
            return false;
    }

    /**
     * Check if a role (or array of roles) is allowed to perform an action (or array of actions).
     *
     * @param int|array $role_id
     * @param string|array $action
     * @return bool
     */
    public function roleAllowed($role_id, $action, $station_id = null)
    {
        $this->init();

        if(is_array($role_id))
        {
            foreach($role_id as $r)
            {
                if($this->roleAllowed($r, $action, $station_id))
                    return true;
            }
            return false;
        }
        else if(is_array($action))
        {
            foreach($action as $a)
            {
                if($this->roleAllowed($role_id, $a, $station_id))
                    return true;
            }
            return false;
        }
        else
        {
            if($role_id == 1) // Default super-administrator role.
                return true;

            if (in_array('administer all', (array)$this->_actions[$role_id]['global']))
                return true;

            if (isset($this->_actions[$role_id]))
            {
                if ($station_id !== null)
                {
                    if (in_array('administer stations', (array)$this->_actions[$role_id]['global']))
                        return true;

                    return in_array($action, (array)$this->_actions[$role_id]['stations'][$station_id]);
                }
                else
                {
                    return in_array($action, (array)$this->_actions[$role_id]['global']);
                }
            }

            return false;
        }
    }

    /**
     * Pretty wrapper around the 'isAllowed' function that throws a UI-friendly exception upon failure.
     *
     * @param $action
     * @throws \App\Exception\NotLoggedIn
     * @throws \App\Exception\PermissionDenied
     */
    public function checkPermission($action, $station_id = null)
    {
        if (!$this->isAllowed($action, $station_id))
        {
            if (!$this->_auth->isLoggedIn())
                throw new \App\Exception\NotLoggedIn();
            else
                throw new \App\Exception\PermissionDenied();
        }
    }
}