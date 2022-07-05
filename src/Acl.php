<?php

declare(strict_types=1);

namespace App;

use App\Entity;
use App\Enums\GlobalPermissions;
use App\Enums\PermissionInterface;
use App\Enums\StationPermissions;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

use function in_array;
use function is_array;

final class Acl
{
    use RequestAwareTrait;

    private array $permissions;

    private ?array $actions;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher
    ) {
        $this->reload();
    }

    /**
     * Force a reload of the internal ACL cache (used in the event of a user status change).
     */
    public function reload(): void
    {
        $sql = $this->em->createQuery(
            <<<'DQL'
                SELECT rp FROM App\Entity\RolePermission rp
            DQL
        );

        $this->actions = [];
        foreach ($sql->getArrayResult() as $row) {
            if ($row['station_id']) {
                $this->actions[$row['role_id']]['stations'][$row['station_id']][] = $row['action_name'];
            } else {
                $this->actions[$row['role_id']]['global'][] = $row['action_name'];
            }
        }
    }

    /**
     * @param string $permission_name
     * @param bool $is_global
     */
    public function isValidPermission(string $permission_name, bool $is_global): bool
    {
        $permissions = $this->listPermissions();

        return $is_global
            ? isset($permissions['global'][$permission_name])
            : isset($permissions['station'][$permission_name]);
    }

    /**
     * @return mixed[]
     */
    public function listPermissions(): array
    {
        if (!isset($this->permissions)) {
            /** @var array<string,array<string, string>> $permissions */
            $permissions = [
                'global' => [],
                'station' => [],
            ];

            foreach (GlobalPermissions::cases() as $globalPermission) {
                $permissions['global'][$globalPermission->value] = $globalPermission->getName();
            }
            foreach (StationPermissions::cases() as $stationPermission) {
                $permissions['station'][$stationPermission->value] = $stationPermission->getName();
            }

            $buildPermissionsEvent = new Event\BuildPermissions($permissions);
            $this->dispatcher->dispatch($buildPermissionsEvent);

            $this->permissions = $buildPermissionsEvent->getPermissions();
        }

        return $this->permissions;
    }

    /**
     * Check if the current user associated with the request has the specified permission.
     *
     * @param array<string|PermissionInterface>|string|PermissionInterface $action
     * @param int|Entity\Station|null $stationId
     */
    public function isAllowed(
        array|string|PermissionInterface $action,
        Entity\Station|int $stationId = null
    ): bool {
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
     * @param array<string|PermissionInterface>|string|PermissionInterface $action
     * @param int|Entity\Station|null $stationId
     */
    public function userAllowed(
        ?Entity\User $user = null,
        array|string|PermissionInterface $action = null,
        Entity\Station|int $stationId = null
    ): bool {
        if (null === $user || null === $action) {
            return false;
        }

        if ($stationId instanceof Entity\Station) {
            $stationId = $stationId->getId();
        }

        $numRoles = $user->getRoles()->count();
        if ($numRoles > 0) {
            if ($numRoles === 1) {
                /** @var Entity\Role $role */
                $role = $user->getRoles()->first();

                return $this->roleAllowed($role->getIdRequired(), $action, $stationId);
            }

            $roles = [];
            foreach ($user->getRoles() as $role) {
                $roles[] = $role->getId();
            }

            return $this->roleAllowed($roles, $action, $stationId);
        }

        return false;
    }

    /**
     * Check if a role (or array of roles) is allowed to perform an action (or array of actions).
     *
     * @param array|int $role_id
     * @param array<string|PermissionInterface>|string|PermissionInterface $action
     * @param int|Entity\Station|null $station_id
     */
    public function roleAllowed(
        array|int $role_id,
        array|string|PermissionInterface $action,
        Entity\Station|int $station_id = null
    ): bool {
        if ($station_id instanceof Entity\Station) {
            $station_id = $station_id->getId();
        }

        if ($action instanceof PermissionInterface) {
            $action = $action->getValue();
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

            if (
                in_array(
                    GlobalPermissions::All->value,
                    (array)$role_actions['global'],
                    true
                )
            ) {
                return true;
            }

            if ($station_id !== null) {
                if (
                    in_array(
                        GlobalPermissions::Stations->value,
                        (array)$role_actions['global'],
                        true
                    )
                ) {
                    return true;
                }

                if (!empty($role_actions['stations'][$station_id])) {
                    if (
                        in_array(
                            StationPermissions::All->value,
                            $role_actions['stations'][$station_id],
                            true
                        )
                    ) {
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
