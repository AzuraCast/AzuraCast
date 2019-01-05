<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;

class RolePermissionRepository extends Repository
{
    protected $permissions;

    public function __construct($em, Mapping\ClassMetadata $class, $permissions)
    {
        parent::__construct($em, $class);

        $this->permissions = $permissions;
    }

    /**
     * TODO: Legacy
     *
     * @return array
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
     * TODO: Legacy functionality only used by existing administration form.
     *
     * @param Entity\Role $role
     * @return array
     */
    public function getActionsForRole(Entity\Role $role): array
    {
        $role_has_action = $this->_em->createQuery('SELECT e FROM '.$this->_entityName.' e WHERE e.role_id = :role_id')
            ->setParameter('role_id', $role->getId())
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
     * TODO: Legacy functionality only used by existing administration form.
     *
     * @param Entity\Role $role
     * @param $post_values
     */
    public function setActionsForRole(Entity\Role $role, $post_values): void
    {
        $this->_em->createQuery('DELETE FROM ' . $this->_entityName . ' rp WHERE rp.role_id = :role_id')
            ->setParameter('role_id', $role->getId())
            ->execute();

        foreach ($post_values as $post_key => $post_value) {
            list($post_key_action, $post_key_id) = explode('_', $post_key);

            if ($post_key_action !== 'actions' || empty($post_value)) {
                continue;
            }

            foreach ((array)$post_value as $action_name) {
                $station = ($post_key_id !== 'global') ? $this->_em->getReference(Entity\Station::class, $post_key_id) : null;

                $record = new Entity\RolePermission($role, $station, $action_name);
                $this->_em->persist($record);
            }
        }

        $this->_em->flush();
    }

    /**
     * Given a multidimensional array in the format:
     * [
     *    'global' => [
     *         'administer all',
     *    ],
     *    'station' => [
     *         1 => [
     *             'administer all',
     *         ],
     *    ]
     * ]
     * ...set the appropriate permissions for the given role.
     *
     * @param Entity\Role $record
     * @param array $value
     */
    public function setPermissions(Entity\Role $record, array $value = []): void
    {
        $perms = $record->getPermissions();

        if ($perms->count() > 0) {
            foreach($perms as $existing_perm) {
                $this->_em->remove($existing_perm);
            }
            $perms->clear();
        }

        if (!empty($value['global'])) {
            foreach ($value['global'] as $perm_name) {
                if ($this->isValidPermission($perm_name, true)) {
                    $perm_record = new Entity\RolePermission($record, null, $perm_name);
                    $this->_em->persist($perm_record);
                    $perms->add($perm_record);
                }
            }
        }

        if (!empty($value['station'])) {
            foreach($value['station'] as $station_id => $station_perms) {
                $station = $this->_em->find(Entity\Station::class, $station_id);

                if ($station instanceof Entity\Station) {
                    foreach($station_perms as $perm_name) {
                        if ($this->isValidPermission($perm_name, false)) {
                            $perm_record = new Entity\RolePermission($record, $station, $perm_name);
                            $this->_em->persist($perm_record);
                            $perms->add($perm_record);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function listPermissions(): array
    {
        $permissions = [];
        foreach($this->permissions as $group => $actions) {
            foreach($actions as $action_id => $action_name) {
                $permissions[$group][] = [
                    'id' => $action_id,
                    'name' => $action_name,
                ];
            }
        }

        return $permissions;
    }

    /**
     * @param $permission_name
     * @param bool $is_global
     * @return bool
     */
    public function isValidPermission($permission_name, $is_global): bool
    {
        return $is_global
            ? isset($this->permissions['global'][$permission_name])
            : isset($this->permissions['station'][$permission_name]);
    }
}
