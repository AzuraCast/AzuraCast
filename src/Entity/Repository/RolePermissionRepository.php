<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Acl;
use App\Doctrine\Repository;
use App\Entity;

/**
 * @extends Repository<Entity\RolePermission>
 */
class RolePermissionRepository extends Repository
{
    /**
     * @return mixed[]
     */
    public function getActionsForAllRoles(): array
    {
        $all_permissions = $this->fetchArray();

        $roles = [];
        foreach ($all_permissions as $row) {
            if ($row['station_id']) {
                $roles[$row['role_id']]['stations'][$row['station_id']][] = $row['action_name'];
            } else {
                $roles[$row['role_id']]['global'][] = $row['action_name'];
            }
        }

        return $roles;
    }

    /**
     * @param Entity\Role $role
     *
     * @return mixed[]
     */
    public function getActionsForRole(Entity\Role $role): array
    {
        $role_has_action = $this->em->createQuery(
            <<<'DQL'
                SELECT e
                FROM App\Entity\RolePermission e
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

    public function ensureSuperAdministratorRole(): Entity\Role
    {
        $superAdminRole = $this->em->createQuery(
            <<<'DQL'
            SELECT r FROM
            App\Entity\Role r LEFT JOIN r.permissions rp
            WHERE rp.station IS NULL AND rp.action_name = :action
            DQL
        )->setParameter('action', Acl::GLOBAL_ALL)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($superAdminRole instanceof Entity\Role) {
            return $superAdminRole;
        }

        $newRole = new Entity\Role();
        $newRole->setName('Super Administrator');
        $this->em->persist($newRole);

        $newPerm = new Entity\RolePermission($newRole, null, Acl::GLOBAL_ALL);
        $this->em->persist($newPerm);

        $this->em->flush();
        return $newRole;
    }
}
