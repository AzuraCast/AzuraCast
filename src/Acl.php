<?php
namespace App;

use App\Entity;

class Acl
{
    public const GLOBAL_ALL            = 'administer all';
    public const GLOBAL_VIEW           = 'view administration';
    public const GLOBAL_LOGS           = 'view system logs';
    public const GLOBAL_SETTINGS       = 'administer settings';
    public const GLOBAL_API_KEYS       = 'administer api keys';
    public const GLOBAL_USERS          = 'administer user accounts';
    public const GLOBAL_PERMISSIONS    = 'administer permissions';
    public const GLOBAL_STATIONS       = 'administer stations';
    public const GLOBAL_CUSTOM_FIELDS  = 'administer custom fields';
    public const GLOBAL_BACKUPS        = 'administer backups';

    public const STATION_ALL           = 'administer all';
    public const STATION_VIEW          = 'view station management';
    public const STATION_REPORTS       = 'view station reports';
    public const STATION_LOGS          = 'view station logs';
    public const STATION_PROFILE       = 'manage station profile';
    public const STATION_BROADCASTING  = 'manage station broadcasting';
    public const STATION_STREAMERS     = 'manage station streamers';
    public const STATION_MOUNTS        = 'manage station mounts';
    public const STATION_REMOTES       = 'manage station remotes';
    public const STATION_MEDIA         = 'manage station media';
    public const STATION_AUTOMATION    = 'manage station automation';
    public const STATION_WEB_HOOKS     = 'manage station web hooks';

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
     * @param string|array $action
     * @param int|Entity\Station|null $station_id
     * @return bool
     */
    public function userAllowed(?Entity\User $user = null, $action, $station_id = null): bool
    {
        if (!($user instanceof Entity\User)) {
            return false;
        }

        if ($station_id instanceof Entity\Station) {
            $station_id = $station_id->getId();
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
     * @param int|Entity\Station|null $station_id
     * @return bool
     */
    public function roleAllowed($role_id, $action, $station_id = null): bool
    {
        if ($station_id instanceof Entity\Station) {
            $station_id = $station_id->getId();
        }

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

            if (\in_array(self::GLOBAL_ALL, (array)$role_actions['global'], true)) {
                return true;
            }

            if ($station_id !== null) {
                if (\in_array(self::GLOBAL_STATIONS, (array)$role_actions['global'], true)) {
                    return true;
                }

                if (!empty($role_actions['stations'][$station_id])) {
                    if (\in_array(self::STATION_ALL, $role_actions['stations'][$station_id], true)) {
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
     * @param string|array $action
     * @param null $station_id
     * @throws Exception\NotLoggedIn
     * @throws Exception\PermissionDenied
     */
    public function checkPermission(?Entity\User $user = null, $action, $station_id = null): void
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
                    self::GLOBAL_SETTINGS        => __('Administer %s', __('Settings')),
                    self::GLOBAL_API_KEYS        => __('Administer %s', __('API Keys')),
                    self::GLOBAL_USERS           => __('Administer %s', __('Users')),
                    self::GLOBAL_PERMISSIONS     => __('Administer %s', __('Permissions')),
                    self::GLOBAL_STATIONS        => __('Administer %s', __('Stations')),
                    self::GLOBAL_CUSTOM_FIELDS   => __('Administer %s', __('Custom Fields')),
                    self::GLOBAL_BACKUPS         => __('Administer %s', __('Backups')),
                ],
                'station' => [
                    self::STATION_ALL            => __('All Permissions'),
                    self::STATION_VIEW           => __('View Station Page'),
                    self::STATION_REPORTS        => __('View Station Reports'),
                    self::STATION_LOGS           => __('View Station Logs'),
                    self::STATION_PROFILE        => __('Manage Station %s', __('Profile')),
                    self::STATION_BROADCASTING   => __('Manage Station %s', __('Broadcasting')),
                    self::STATION_STREAMERS      => __('Manage Station %s', __('Streamers')),
                    self::STATION_MOUNTS         => __('Manage Station %s', __('Mount Points')),
                    self::STATION_REMOTES        => __('Manage Station %s', __('Remote Relays')),
                    self::STATION_MEDIA          => __('Manage Station %s', __('Media')),
                    self::STATION_AUTOMATION     => __('Manage Station %s', __('Automation')),
                    self::STATION_WEB_HOOKS      => __('Manage Station %s', __('Web Hooks')),
                ]
            ];
        }

        return $permissions;
    }

    /**
     * @param string $permission_name
     * @param bool $is_global
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
