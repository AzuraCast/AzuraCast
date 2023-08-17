<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Enums\GlobalPermissions;

/**
 * @extends Repository<RolePermission>
 */
final class RolePermissionRepository extends Repository
{
    protected string $entityClass = RolePermission::class;

    /**
     * @param Role $role
     *
     * @return mixed[]
     */
    public function getActionsForRole(Role $role): array
    {
        $roleHasAction = $this->em->createQuery(
            <<<'DQL'
                SELECT e
                FROM App\Entity\RolePermission e
                WHERE e.role_id = :role_id
            DQL
        )->setParameter('role_id', $role->getId())
            ->getArrayResult();

        $result = [];
        foreach ($roleHasAction as $row) {
            if ($row['station_id']) {
                $result['actions_' . $row['station_id']][] = $row['action_name'];
            } else {
                $result['actions_global'][] = $row['action_name'];
            }
        }

        return $result;
    }

    public function ensureSuperAdministratorRole(): Role
    {
        $superAdminRole = $this->em->createQuery(
            <<<'DQL'
            SELECT r FROM
            App\Entity\Role r LEFT JOIN r.permissions rp
            WHERE rp.station IS NULL AND rp.action_name = :action
            DQL
        )->setParameter('action', GlobalPermissions::All->value)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($superAdminRole instanceof Role) {
            return $superAdminRole;
        }

        $newRole = new Role();
        $newRole->setName('Super Administrator');
        $this->em->persist($newRole);

        $newPerm = new RolePermission($newRole, null, GlobalPermissions::All);
        $this->em->persist($newPerm);

        $this->em->flush();
        return $newRole;
    }
}
