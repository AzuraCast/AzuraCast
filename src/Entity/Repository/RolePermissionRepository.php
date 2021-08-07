<?php

declare(strict_types=1);

namespace App\Entity\Repository;

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

    /**
     * @param Entity\Role $role
     * @param array $post_values
     */
    public function setActionsForRole(Entity\Role $role, array $post_values): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\RolePermission rp
                WHERE rp.role_id = :role_id
            DQL
        )->setParameter('role_id', $role->getId())
            ->execute();

        foreach ($post_values as $post_key => $post_value) {
            if (str_contains($post_key, '_')) {
                [$post_key_action, $post_key_id] = explode('_', $post_key);
            } else {
                $post_key_action = $post_key;
                $post_key_id = null;
            }

            if ($post_key_action !== 'actions' || empty($post_value)) {
                continue;
            }

            foreach ((array)$post_value as $action_name) {
                $station = ($post_key_id !== 'global') ? $this->em->getReference(
                    Entity\Station::class,
                    $post_key_id
                ) : null;

                $record = new Entity\RolePermission($role, $station, $action_name);
                $this->em->persist($record);
            }
        }

        $this->em->flush();
    }
}
