<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Enums\GlobalPermissions;
use App\Entity\Role;
use App\Entity\RolePermission;

/**
 * @extends Repository<\App\Entity\RolePermission>
 */
final class RolePermissionRepository extends Repository
{
    /**
     * @param \App\Entity\Role $role
     *
     * @return mixed[]
     */
    public function getActionsForRole(Role $role): array
    {
        $role_has_action = $this->em->createQuery(
            <<<'DQL'
                SELECT e
                FROM App\\App\Entity\RolePermission e
                WHERE e.role_id = :role_id
            DQL
        )->setParameter('role_id', $role->getId())
            ->getArrayResult();

        $result = [];
        foreach ($role_has_action as $row) {
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
            App\\App\Entity\Role r LEFT JOIN r.permissions rp
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
