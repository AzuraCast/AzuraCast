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
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
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
     * @param int|Station|null $stationId
     */
    public function isAllowed(
        array|string|PermissionInterface $action,
        Station|int $stationId = null
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
     * @param User|null $user
     * @param array<string|PermissionInterface>|string|PermissionInterface $action
     * @param int|Station|null $stationId
     */
    public function userAllowed(
        ?User $user = null,
        array|string|PermissionInterface $action = null,
        Station|int $stationId = null
    ): bool {
        if (null === $user || null === $action) {
            return false;
        }

        if ($stationId instanceof Station) {
            $stationId = $stationId->getId();
        }

        $numRoles = $user->getRoles()->count();
        if ($numRoles > 0) {
            if ($numRoles === 1) {
                /** @var Role $role */
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
     * @param array|int $roleId
     * @param array<(string | PermissionInterface)>|string|PermissionInterface $action
     * @param int|Station|null $stationId
     */
    public function roleAllowed(
        array|int $roleId,
        array|string|PermissionInterface $action,
        Station|int $stationId = null
    ): bool {
        if ($stationId instanceof Station) {
            $stationId = $stationId->getId();
        }

        if ($action instanceof PermissionInterface) {
            $action = $action->getValue();
        }

        // Iterate through an array of roles and return with the first "true" response, or "false" otherwise.
        if (is_array($roleId)) {
            foreach ($roleId as $r) {
                if ($this->roleAllowed($r, $action, $stationId)) {
                    return true;
                }
            }

            return false;
        }

        // If multiple actions are supplied, treat the list as "x OR y OR z", returning if any action is allowed.
        if (is_array($action)) {
            foreach ($action as $a) {
                if ($this->roleAllowed($roleId, $a, $stationId)) {
                    return true;
                }
            }

            return false;
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
