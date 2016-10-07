<?php
/**
 * Access Control List (ACL) manager
 */

namespace App;

use Entity\RolePermission;
use Entity\User;
use Entity\Role;

class Acl
{
    /** @var \Doctrine\ORM\EntityManager  */
    protected $_em;

    /** @var Auth */
    protected $_auth;

    /** @var array|null An array of actions enabled by each role. */
    protected $_actions = NULL;

    public function __construct(\Doctrine\ORM\EntityManager $em, Auth $auth)
    {
        $this->_em = $em;
        $this->_auth = $auth;
    }

    /**
     * Initialize role/actions cache upon the first permission check.
     */
    protected function init()
    {
        if (null === $this->_actions)
            $this->_actions = $this->_em->getRepository(RolePermission::class)->getActionsForAllRoles();
    }

    /**
     * Check if a specified User entity is allowed to perform an action (or array of actions).
     *
     * @param string|array $action
     * @param User|null $user
     * @return mixed
     */
    public function userAllowed($action, User $user = null)
    {
        static $roles;
        static $cache;

        // Make all actions lower-case and sort alphabetically (so memoization returns the same result).
        $action = array_map('strtolower', (array)$action);
        asort($action);

        $memoize = md5(serialize($action));
        $user_id = ($user instanceof User) ? $user->id : 'anonymous';

        if( !isset($cache[$user_id][$memoize]) )
        {
            if($user instanceof User)
            {
                if(!isset($roles[$user_id]))
                {
                    $roles[$user_id] = array();

                    if (count($user->roles) > 0)
                    {
                        foreach($user->roles as $role)
                            $roles[$user_id][] = $role->id;
                    }
                }

                $cache[$user_id][$memoize] = $this->roleAllowed($roles[$user_id], $action);
            }
            else
            {
                $cache[$user_id][$memoize] = $this->roleAllowed(array('Unauthenticated'), $action);
            }
        }

        return $cache[$user_id][$memoize];
    }

    /**
     * Check if the currently logged-in user can perform a specified action.
     *
     * @param string $action
     * @return bool|mixed
     */
    public function isAllowed($action)
    {
        static $is_logged_in, $user;

        if ($is_logged_in === NULL)
        {
            $user = $this->_auth->getLoggedInUser();
            $is_logged_in = ($user instanceof User);
        }

        if ($action == "is logged in")
            return ($is_logged_in);
        elseif ($action == "is not logged in")
            return (!$is_logged_in);
        elseif ($is_logged_in)
            return $this->userAllowed($action, $user);
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
    public function roleAllowed($role_id, $action)
    {
        $this->init();

        if(is_array($role_id))
        {
            foreach($role_id as $r)
            {
                if($this->roleAllowed($r, $action))
                    return true;
            }
            return false;
        }
        else if(is_array($action))
        {
            foreach($action as $a)
            {
                if($this->roleAllowed($role_id, $a))
                    return true;
            }
            return false;
        }
        else
        {
            if($role_id == 1) // Default super-administrator role.
                return true;

            if (in_array('administer all', (array)$this->_actions[$role_id]))
                return true;

            if (isset($this->_actions[$role_id]) && in_array($action, $this->_actions[$role_id]))
                return true;

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
    public function checkPermission($action)
    {
        if (!$this->isAllowed($action))
        {
            if (!$this->_auth->isLoggedIn())
                throw new \App\Exception\NotLoggedIn();
            else
                throw new \App\Exception\PermissionDenied();
        }
    }
}