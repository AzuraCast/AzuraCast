<?php

declare(strict_types=1);

namespace App;

use App\Entity;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

use function in_array;
use function is_array;

class Acl
{
    use RequestAwareTrait;

    public const GLOBAL_ALL = 'administer all';
    public const GLOBAL_VIEW = 'view administration';
    public const GLOBAL_LOGS = 'view system logs';
    public const GLOBAL_SETTINGS = 'administer settings';
    public const GLOBAL_API_KEYS = 'administer api keys';
    public const GLOBAL_STATIONS = 'administer stations';
    public const GLOBAL_CUSTOM_FIELDS = 'administer custom fields';
    public const GLOBAL_BACKUPS = 'administer backups';
    public const GLOBAL_STORAGE_LOCATIONS = 'administer storage locations';

    public const STATION_ALL = 'administer all';
    public const STATION_VIEW = 'view station management';
    public const STATION_REPORTS = 'view station reports';
    public const STATION_LOGS = 'view station logs';
    public const STATION_PROFILE = 'manage station profile';
    public const STATION_BROADCASTING = 'manage station broadcasting';
    public const STATION_STREAMERS = 'manage station streamers';
    public const STATION_MOUNTS = 'manage station mounts';
    public const STATION_REMOTES = 'manage station remotes';
    public const STATION_MEDIA = 'manage station media';
    public const STATION_AUTOMATION = 'manage station automation';
    public const STATION_WEB_HOOKS = 'manage station web hooks';
    public const STATION_PODCASTS = 'manage station podcasts';

    protected array $permissions;

    protected ?array $actions;

    public function __construct(
        protected Entity\Repository\RolePermissionRepository $permissionRepo,
        protected EventDispatcherInterface $dispatcher
    ) {
        $this->reload();
    }

    /**
     * Force a reload of the internal ACL cache (used in the event of a user status change).
     */
    public function reload(): void
    {
        $this->actions = $this->permissionRepo->getActionsForAllRoles();
    }

    /**
     * @param string $permission_name
     * @param bool $is_global
     */
    public function isValidPermission(string $permission_name, bool $is_global): bool
    {
        return $is_global
            ? isset($this->permissions['global'][$permission_name])
            : isset($this->permissions['station'][$permission_name]);
    }

    /**
     * @return mixed[]
     */
    public function listPermissions(): array
    {
        if (!isset($this->permissions)) {
            /** @var array<string,array> $permissions */
            $permissions = [
                'global' => [
                    self::GLOBAL_ALL => __('All Permissions'),
                    self::GLOBAL_VIEW => __('View Administration Page'),
                    self::GLOBAL_LOGS => __('View System Logs'),
                    self::GLOBAL_SETTINGS => __('Administer Settings'),
                    self::GLOBAL_API_KEYS => __('Administer API Keys'),
                    self::GLOBAL_STATIONS => __('Administer Stations'),
                    self::GLOBAL_CUSTOM_FIELDS => __('Administer Custom Fields'),
                    self::GLOBAL_BACKUPS => __('Administer Backups'),
                    self::GLOBAL_STORAGE_LOCATIONS => __('Administer Storage Locations'),
                ],
                'station' => [
                    self::STATION_ALL => __('All Permissions'),
                    self::STATION_VIEW => __('View Station Page'),
                    self::STATION_REPORTS => __('View Station Reports'),
                    self::STATION_LOGS => __('View Station Logs'),
                    self::STATION_PROFILE => __('Manage Station Profile'),
                    self::STATION_BROADCASTING => __('Manage Station Broadcasting'),
                    self::STATION_STREAMERS => __('Manage Station Streamers'),
                    self::STATION_MOUNTS => __('Manage Station Mount Points'),
                    self::STATION_REMOTES => __('Manage Station Remote Relays'),
                    self::STATION_MEDIA => __('Manage Station Media'),
                    self::STATION_AUTOMATION => __('Manage Station Automation'),
                    self::STATION_WEB_HOOKS => __('Manage Station Web Hooks'),
                    self::STATION_PODCASTS => __('Manage Station Podcasts'),
                ],
            ];

            $buildPermissionsEvent = new Event\BuildPermissions($permissions);
            $this->dispatcher->dispatch($buildPermissionsEvent);

            $this->permissions = $buildPermissionsEvent->getPermissions();
        }

        return $this->permissions;
    }

    /**
     * Check if the current user associated with the request has the specified permission.
     *
     * @param array|string $action
     * @param int|Entity\Station|null $stationId
     */
    public function isAllowed(array|string $action, Entity\Station|int $stationId = null): bool
    {
        if ($this->request instanceof ServerRequestInterface) {
            $user = $this->request->getAttribute(ServerRequest::ATTR_USER);
            return $this->userAllowed($user, $action, $stationId);
        }

        return false;
    }

    /**
     * Check if a specified User entity is allowed to perform an action (or array of actions).
     *
     * @param Entity\User|null $user
     * @param array|string $action
     * @param int|Entity\Station|null $stationId
     */
    public function userAllowed(
        ?Entity\User $user = null,
        array|string $action,
        Entity\Station|int $stationId = null
    ): bool {
        if (null === $user) {
            return false;
        }

        if ($stationId instanceof Entity\Station) {
            $stationId = $stationId->getId();
        }

        $num_roles = $user->getRoles()->count();
        if ($num_roles > 0) {
            if ($num_roles === 1) {
                $role = $user->getRoles()->first();
                return $this->roleAllowed($role->getId(), $action, $stationId);
            }

            $roles = [];
            if ($user->getRoles()->count() > 0) {
                foreach ($user->getRoles() as $role) {
                    $roles[] = $role->getId();
                }
            }

            return $this->roleAllowed($roles, $action, $stationId);
        }

        return false;
    }

    /**
     * Check if a role (or array of roles) is allowed to perform an action (or array of actions).
     *
     * @param array|int $role_id
     * @param array|string $action
     * @param int|Entity\Station|null $station_id
     */
    public function roleAllowed(array|int $role_id, array|string $action, Entity\Station|int $station_id = null): bool
    {
        if ($station_id instanceof Entity\Station) {
            $station_id = $station_id->getId();
        }

        // Iterate through an array of roles and return with the first "true" response, or "false" otherwise.
        if (is_array($role_id)) {
            foreach ($role_id as $r) {
                if ($this->roleAllowed($r, $action, $station_id)) {
                    return true;
                }
            }

            return false;
        }

        // If multiple actions are supplied, treat the list as "x OR y OR z", returning if any action is allowed.
        if (is_array($action)) {
            foreach ($action as $a) {
                if ($this->roleAllowed($role_id, $a, $station_id)) {
                    return true;
                }
            }

            return false;
        }

        if (!empty($this->actions[$role_id])) {
            $role_actions = (array)$this->actions[$role_id];

            if (in_array(self::GLOBAL_ALL, (array)$role_actions['global'], true)) {
                return true;
            }

            if ($station_id !== null) {
                if (in_array(self::GLOBAL_STATIONS, (array)$role_actions['global'], true)) {
                    return true;
                }

                if (!empty($role_actions['stations'][$station_id])) {
                    if (in_array(self::STATION_ALL, $role_actions['stations'][$station_id], true)) {
                        return true;
                    }

                    return in_array($action, (array)$role_actions['stations'][$station_id], true);
                }
            } else {
                return in_array($action, (array)$role_actions['global'], true);
            }
        }

        return false;
    }
}
