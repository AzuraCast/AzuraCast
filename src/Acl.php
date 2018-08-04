<?php
namespace App;

class Acl
{
    /** @var Entity\Repository\RolePermissionRepository */
    protected $permission_repo;

    /** @var array|null An array of actions enabled by each role. */
    protected $_actions = null;

    public function __construct(Entity\Repository\RolePermissionRepository $permission_repo)
    {
        $this->permission_repo = $permission_repo;
        $this->reload();
    }

    /**
     * Force a reload of the internal ACL cache (used in the event of a user status change.
     */
    public function reload(): void
    {
        $this->_actions = $this->permission_repo->getActionsForAllRoles();
        $this->_roles = null;
        $this->_cache = null;
    }

    /**
     * Check if a specified User entity is allowed to perform an action (or array of actions).
     *
     * @param Entity\User|null $user
     * @param $action
     * @param null $station_id
     * @return bool
     */
    public function userAllowed(?Entity\User $user = null, $action, $station_id = null): bool
    {
        if (!($user instanceof Entity\User)) {
            return false;
        }

        $num_roles = $user->getRoles()->count();
        if ($num_roles > 0) {
            if ($num_roles === 1) {
                $role = $user->getRoles()->first();
                return $this->roleAllowed($role->getId(), $action, $station_id);
            }

            $roles = [];
            if ($user->getRoles()->count() > 0) {
                foreach ($user->getRoles() as $role) {
                    $roles[] = $role->getId();
                }
            }

            return $this->roleAllowed($roles, $action, $station_id);
        }

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
        // Iterate through an array of roles and return with the first "true" response, or "false" otherwise.
        if (\is_array($role_id)) {
            foreach ($role_id as $r) {
                if ($this->roleAllowed($r, $action, $station_id)) {
                    return true;
                }
            }

            return false;
        }

        // If multiple actions are supplied, treat the list as "x OR y OR z", returning if any action is allowed.
        if (\is_array($action)) {
            foreach ($action as $a) {
                if ($this->roleAllowed($role_id, $a, $station_id)) {
                    return true;
                }
            }

            return false;
        }

        if (!empty($this->_actions[$role_id])) {
            $role_actions = (array)$this->_actions[$role_id];

            if (\in_array('administer all', (array)$role_actions['global'], true)) {
                return true;
            }

            if ($station_id !== null) {
                if (\in_array('administer stations', (array)$role_actions['global'], true)) {
                    return true;
                }

                if (!empty($role_actions['stations'][$station_id])) {
                    if (\in_array('administer all', $role_actions['stations'][$station_id], true)) {
                        return true;
                    }

                    return \in_array($action, (array)$role_actions['stations'][$station_id], true);
                }
            } else {
                return \in_array($action, (array)$role_actions['global'], true);
            }
        }

        return false;
    }

    /**
     * Wrapper around the 'userAllowed' function that throws a UI-friendly exception upon failure.
     *
     * @param Entity\User|null $user
     * @param $action
     * @param null $station_id
     * @throws Exception\NotLoggedIn
     * @throws Exception\PermissionDenied
     */
    public function checkPermission(?Entity\User $user = null, $action, $station_id = null)
    {
        if (!($user instanceof Entity\User)) {
            throw new Exception\NotLoggedIn;
        }

        if (!$this->userAllowed($user, $action, $station_id)) {
            throw new Exception\PermissionDenied;
        }
    }
}
