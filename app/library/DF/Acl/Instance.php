<?php
/**
 * Access Control List (ACL) manager
 */

namespace DF\Acl;

use \Entity\User;
use \Entity\Role;

class Instance
{
	protected $_actions = NULL;
	
	public function __construct()
	{}
	
	public function init()
	{
		if (null === $this->_actions)
		{
			$this->_actions = array();
            
            $em = \Zend_Registry::get('em');
            $query = $em->createQuery('SELECT r, a FROM \Entity\Role r JOIN r.actions a');
            $roles_with_actions = $query->getArrayResult();
			
			foreach($roles_with_actions as $role)
			{
				foreach((array)$role['actions'] as $action)
				{
					$this->_actions[$role['id']][] = $action['name'];
				}
			}
		}
	}

	public function userAllowed($action, User $user = null)
    {
        static $roles;
        static $cache;
        
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
						{
							$roles[$user_id][] = $role->id;
						}
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

	public function isAllowed($action)
    {
		$auth = \Zend_Registry::get('auth');
		$user = $auth->getLoggedInUser();
		$is_logged_in = ($user instanceof User);
		
		if ($action == "is logged in")
			return ($is_logged_in);
		elseif ($action == "is not logged in")
			return (!$is_logged_in);
		elseif ($is_logged_in)
			return $this->userAllowed($action, $user);
		else
			return false;
    }

	public function roleAllowed($role_id, $action, $exact_only = FALSE)
    {
		$this->init();
		
        if(is_array($role_id))
        {
            foreach($role_id as $r)
            {
                if($this->roleAllowed($r, $action)) //Once we've gotten a true, move forward
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
			// Without "exact_only" flag, matches based on root-level access are permitted.
			if (!$exact_only)
			{
				if($role_id == 1) //ROOT
					return true;
				
				if (in_array('administer all', (array)$this->_actions[$role_id]))
					return true;
			}
			
			if (isset($this->_actions[$role_id]) && in_array($action, $this->_actions[$role_id]))
				return true;
			
			return false;
        }
    }
	
	/**
	 * Pretty wrapper around the 'isAllowed' function that throws a UI-friendly exception upon failure.
	 */
	public function checkPermission($action)
	{
		$auth = \Zend_Registry::get('auth');
		
		if (!$this->isAllowed($action))
		{
			if (!$auth->isLoggedIn())
				throw new \DF\Exception\NotLoggedIn();
			else
				throw new \DF\Exception\PermissionDenied();
		}
	}
}