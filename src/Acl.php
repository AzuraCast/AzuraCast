<?php
namespace App;

class Acl
{
    const GLOBAL_ALL            = 'administer all';
    const GLOBAL_VIEW           = 'view administration';
    const GLOBAL_LOGS           = 'view system logs';
    const GLOBAL_SETTINGS       = 'administer settings';
    const GLOBAL_API_KEYS       = 'administer api keys';
    const GLOBAL_USERS          = 'administer user accounts';
    const GLOBAL_PERMISSIONS    = 'administer permissions';
    const GLOBAL_STATIONS       = 'administer stations';
    const GLOBAL_CUSTOM_FIELDS  = 'administer custom fields';

    const STATION_ALL           = 'administer all';
    const STATION_VIEW          = 'view station management';
    const STATION_REPORTS       = 'view station reports';
    const STATION_LOGS          = 'view station logs';
    const STATION_PROFILE       = 'manage station profile';
    const STATION_BROADCASTING  = 'manage station broadcasting';
    const STATION_STREAMERS     = 'manage station streamers';
    const STATION_MOUNTS        = 'manage station mounts';
    const STATION_REMOTES       = 'manage station remotes';
    const STATION_MEDIA         = 'manage station media';
    const STATION_AUTOMATION    = 'manage station automation';
    const STATION_WEB_HOOKS     = 'manage station web hooks';

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

    /**
     * @return array
     */
    public static function listPermissions(): array
    {
        static $permissions;

        if (null === $permissions) {
            $permissions= [
                'global' => [
                    self::GLOBAL_ALL             => __('All Permissions'),
                    self::GLOBAL_VIEW            => __('View Administration Page'),
                    self::GLOBAL_LOGS            => __('View System Logs'),
                    self::GLOBAL_SETTINGS        => sprintf(__('Administer %s'), __('Settings')),
                    self::GLOBAL_API_KEYS        => sprintf(__('Administer %s'), __('API Keys')),
                    self::GLOBAL_USERS           => sprintf(__('Administer %s'), __('Users')),
                    self::GLOBAL_PERMISSIONS     => sprintf(__('Administer %s'), __('Permissions')),
                    self::GLOBAL_STATIONS        => sprintf(__('Administer %s'), __('Stations')),
                    self::GLOBAL_CUSTOM_FIELDS   => sprintf(__('Administer %s'), __('Custom Fields')),
                ],
                'station' => [
                    self::STATION_ALL            => __('All Permissions'),
                    self::STATION_VIEW           => __('View Station Page'),
                    self::STATION_REPORTS        => __('View Station Reports'),
                    self::STATION_LOGS           => __('View Station Logs'),
                    self::STATION_PROFILE        => sprintf(__('Manage Station %s'), __('Profile')),
                    self::STATION_BROADCASTING   => sprintf(__('Manage Station %s'), __('Broadcasting')),
                    self::STATION_STREAMERS      => sprintf(__('Manage Station %s'), __('Streamers')),
                    self::STATION_MOUNTS         => sprintf(__('Manage Station %s'), __('Mount Points')),
                    self::STATION_REMOTES        => sprintf(__('Manage Station %s'), __('Remote Relays')),
                    self::STATION_MEDIA          => sprintf(__('Manage Station %s'), __('Media')),
                    self::STATION_AUTOMATION     => sprintf(__('Manage Station %s'), __('Automation')),
                    self::STATION_WEB_HOOKS      => sprintf(__('Manage Station %s'), __('Web Hooks')),
                ]
            ];
        }

        return $permissions;
    }

    /**
     * @param $permission_name
     * @param $is_global
     * @return bool
     */
    public static function isValidPermission($permission_name, $is_global): bool
    {
        $permissions = self::listPermissions();

        return $is_global
            ? isset($permissions['global'][$permission_name])
            : isset($permissions['station'][$permission_name]);
    }
}
