<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="role_permissions", uniqueConstraints={
 *   @UniqueConstraint(name="role_permission_unique_idx", columns={"role_id","action_name","station_id"})
 * })
 * @Entity(repositoryClass="RolePermissionRepository")
 */
class RolePermission extends \App\Doctrine\Entity
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="role_id", type="integer") */
    protected $role_id;

    /**
     * @ManyToOne(targetEntity="Role", inversedBy="permissions")
     * @JoinColumns({
     *   @JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $role;

    /** @Column(name="action_name", type="string", length=50, nullable=false) */
    protected $action_name;

    /** @Column(name="station_id", type="integer", nullable=true) */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}

use App\Doctrine\Repository;

class RolePermissionRepository extends Repository
{
    public function getActionsForAllRoles()
    {
        $all_permissions = $this->fetchArray();

        $roles = [];
        foreach($all_permissions as $row)
        {
            if ($row['station_id'])
                $roles[$row['role_id']]['stations'][$row['station_id']][] = $row['action_name'];
            else
                $roles[$row['role_id']]['global'][] = $row['action_name'];
        }

        return $roles;
    }

    public function getActionsForRole(Role $role)
    {
        $role_has_action = $this->findBy(['role_id' => $role->id]);

        $result = [];
        foreach($role_has_action as $row)
        {
            if ($row['station_id'])
                $result['actions_'.$row['station_id']][] = $row['action_name'];
            else
                $result['actions_global'][] = $row['action_name'];
        }

        return $result;
    }

    public function setActionsForRole(Role $role, $post_values)
    {
        $this->_em->createQuery('DELETE FROM '.$this->_entityName.' rp WHERE rp.role_id = :role_id')
            ->setParameter('role_id', $role->id)
            ->execute();

        foreach($post_values as $post_key => $post_value)
        {
            list($post_key_action, $post_key_id) = explode('_', $post_key);

            if ($post_key_action !== 'actions' || empty($post_value))
                continue;

            foreach((array)$post_value as $action_name)
            {
                $record_info = [
                    'role_id' => $role->id,
                    'action_name' => $action_name,
                ];

                if ($post_key_id !== 'global')
                    $record_info['station_id'] = $post_key_id;

                $record = new RolePermission;
                $record->fromArray($this->_em, $record_info);

                $this->_em->persist($record);
            }

            $this->_em->flush();
        }

    }
}