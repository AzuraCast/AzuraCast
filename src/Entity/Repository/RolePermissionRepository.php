<?php
namespace Entity\Repository;

use Entity;

class RolePermissionRepository extends \App\Doctrine\Repository
{
    public function getActionsForAllRoles()
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

    public function getActionsForRole(Entity\Role $role)
    {
        $role_has_action = $this->findBy(['role_id' => $role->id]);

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

    public function setActionsForRole(Entity\Role $role, $post_values)
    {
        $this->_em->createQuery('DELETE FROM ' . $this->_entityName . ' rp WHERE rp.role_id = :role_id')
            ->setParameter('role_id', $role->id)
            ->execute();

        foreach ($post_values as $post_key => $post_value) {
            list($post_key_action, $post_key_id) = explode('_', $post_key);

            if ($post_key_action !== 'actions' || empty($post_value)) {
                continue;
            }

            foreach ((array)$post_value as $action_name) {
                $record_info = [
                    'role_id' => $role->id,
                    'action_name' => $action_name,
                ];

                if ($post_key_id !== 'global') {
                    $record_info['station_id'] = $post_key_id;
                }

                $record = new Entity\RolePermission;
                $record->fromArray($this->_em, $record_info);

                $this->_em->persist($record);
            }

            $this->_em->flush();
        }

    }
}