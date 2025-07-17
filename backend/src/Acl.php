<?php

declare(strict_types=1);

namespace App;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Role;
use App\Entity\Station;
use App\Entity\User;
use App\Enums\GlobalPermissions;
use App\Enums\PermissionInterface;
use App\Enums\StationPermissions;
use App\Exception\Http\InvalidRequestAttribute;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use Psr\EventDispatcher\EventDispatcherInterface;

use function in_array;
use function is_array;

/**
 * @phpstan-type PermissionsArray array{
 *     global: array<string, string>,
 *     station: array<string, string>
 * }
 */
final class Acl
{
    use RequestAwareTrait;

    /**
     * @var PermissionsArray
     */
    private array $permissions;

    /**
     * @var array<
     *     int,
     *     array{
     *         stations?: array<int, array<string>>,
     *         global?: array<string>
     *     }
     * >
     */
    private array $actions;

    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
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
                SELECT IDENTITY(rp.station) AS station_id, IDENTITY(rp.role) AS role_id, rp.action_name 
                FROM App\Entity\RolePermission rp
            DQL
        );

        $this->actions = [];
        foreach ($sql->toIterable() as $row) {
            if ($row['station_id']) {
                $this->actions[$row['role_id']]['stations'][$row['station_id']][] = $row['action_name'];
            } else {
                $this->actions[$row['role_id']]['global'][] = $row['action_name'];
            }
        }
    }

    /**
     * @param string $permissionName
     * @param bool $isGlobal
     */
    public function isValidPermission(string $permissionName, bool $isGlobal): bool
    {
        $permissions = $this->listPermissions();

        return $isGlobal
            ? isset($permissions['global'][$permissionName])
            : isset($permissions['station'][$permissionName]);
    }

    /**
     * @return PermissionsArray
     */
    public function listPermissions(): array
    {
        if (!isset($this->permissions)) {
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
     * @param int|Station|null $stationId
     */
    public function isAllowed(
        array|string|PermissionInterface $action,
        Station|int|null $stationId = null
    ): bool {
        if ($this->request instanceof ServerRequest) {
            try {
                $user = $this->request->getUser();
                return $this->userAllowed($action, $user, $stationId);
            } catch (InvalidRequestAttribute) {
            }
        }

        return false;
    }

    /**
     * Check if a specified User entity is allowed to perform an action (or array of actions).
     *
     * @param array<string|PermissionInterface>|string|PermissionInterface $action
     * @param User|null $user
     * @param int|Station|null $stationId
     */
    public function userAllowed(
        array|string|PermissionInterface $action,
        ?User $user = null,
        Station|int|null $stationId = null
    ): bool {
        if (null === $user) {
            return false;
        }

        if ($stationId instanceof Station) {
            $stationId = $stationId->id;
        }

        $numRoles = $user->roles->count();
        if ($numRoles > 0) {
            if ($numRoles === 1) {
                /** @var Role $role */
                $role = $user->roles->first();

                return $this->roleAllowed($role->id, $action, $stationId);
            }

            $roles = [];
            foreach ($user->roles as $role) {
                $roles[] = $role->id;
            }

            return $this->roleAllowed($roles, $action, $stationId);
        }

        return false;
    }

    /**
     * Check if a role (or array of roles) is allowed to perform an action (or array of actions).
     *
     * @param array|int $roleId
     * @param array<(string | PermissionInterface)>|string|PermissionInterface $action
     * @param int|Station|null $stationId
     */
    public function roleAllowed(
        array|int $roleId,
        array|string|PermissionInterface $action,
        Station|int|null $stationId = null
    ): bool {
        if ($stationId instanceof Station) {
            $stationId = $stationId->id;
        }

        if ($action instanceof PermissionInterface) {
            $action = $action->getValue();
        }

        // Iterate through an array of roles and return with the first "true" response, or "false" otherwise.
        if (is_array($roleId)) {
            return array_any($roleId, fn($r) => $this->roleAllowed($r, $action, $stationId));
        }

        // If multiple actions are supplied, treat the list as "x OR y OR z", returning if any action is allowed.
        if (is_array($action)) {
            return array_any($action, fn($a) => $this->roleAllowed($roleId, $a, $stationId));
        }

        if (!empty($this->actions[$roleId])) {
            $roleActions = (array)$this->actions[$roleId];

            if (
                in_array(
                    GlobalPermissions::All->value,
                    (array)($roleActions['global'] ?? []),
                    true
                )
            ) {
                return true;
            }

            if ($stationId !== null) {
                if (
                    in_array(
                        GlobalPermissions::Stations->value,
                        (array)($roleActions['global'] ?? []),
                        true
                    )
                ) {
                    return true;
                }

                if (!empty($roleActions['stations'][$stationId])) {
                    if (
                        in_array(
                            StationPermissions::All->value,
                            $roleActions['stations'][$stationId],
                            true
                        )
                    ) {
                        return true;
                    }

                    return in_array($action, (array)$roleActions['stations'][$stationId], true);
                }
            } else {
                return in_array(
                    $action,
                    (array)($roleActions['global'] ?? []),
                    true
                );
            }
        }

        return false;
    }
}
