<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="role_has_actions", uniqueConstraints={
 *   @UniqueConstraint(name="role_action_unique_idx", columns={"role_id","action_id","station_id"})
 * })
 * @Entity(repositoryClass="RoleHasActionRepository")
 */
class RoleHasAction extends \App\Doctrine\Entity
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
     * @ManyToOne(targetEntity="Role", inversedBy="has_actions")
     * @JoinColumns({
     *   @JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $role;

    /** @Column(name="action_id", type="integer") */
    protected $action_id;

    /**
     * @ManyToOne(targetEntity="Action", inversedBy="has_roles")
     * @JoinColumns({
     *   @JoinColumn(name="action_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $action;

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

class RoleHasActionRepository extends Repository
{
    public function getActionsForAllRoles()
    {
        $role_has_actions = $this->_em->createQuery('SELECT rha, a FROM '.$this->_entityName.' rha JOIN rha.action a')
            ->getArrayResult();

        $roles = [];

        foreach($role_has_actions as $row)
        {
            if ($row['station_id'])
                $roles[$row['role_id']]['stations'][$row['station_id']][] = $row['action']['name'];
            else
                $roles[$row['role_id']]['global'][] = $row['action']['name'];
        }

        return $roles;
    }

    public function getSelectableActions()
    {
        $actions = [];

        $all_actions = $this->_em->getRepository(Action::class)->fetchArray();
        $all_stations = $this->_em->getRepository(Station::class)->fetchArray();

        foreach($all_actions as $action)
        {
            if ($action['is_global'])
            {
                $actions['global'][$action['id']] = $action['name'];
            }
            else
            {
                foreach($all_stations as $station)
                {
                    if (!isset($actions['stations'][$station['id']]))
                        $actions['stations'][$station['id']] = ['name' => $station['name'], 'actions' => []];

                    $actions['stations'][$station['id']]['actions'][$action['id']] = $action['name'];
                }
            }
        }

        return $actions;
    }

    public function getActionsForRole(Role $role)
    {
        $role_has_action = $this->findBy(['role_id' => $role->id]);

        $result = [];
        foreach($role_has_action as $row)
        {
            if ($row['station_id'])
                $result['actions_'.$row['station_id']][] = $row['action_id'];
            else
                $result['actions_global'][] = $row['action_id'];
        }

        return $result;
    }

    public function setActionsForRole(Role $role, $post_values)
    {
        $this->_em->createQuery('DELETE FROM '.$this->_entityName.' rha WHERE rha.role_id = :role_id')
            ->setParameter('role_id', $role->id)
            ->execute();

        foreach($post_values as $post_key => $post_value)
        {
            list($post_key_action, $post_key_id) = explode('_', $post_key);

            if ($post_key_action !== 'actions' || empty($post_value))
                continue;

            foreach((array)$post_value as $action_id)
            {
                $record_info = [
                    'role_id' => $role->id,
                    'action_id' => $action_id
                ];

                if ($post_key_id !== 'global')
                    $record_info['station_id'] = $post_key_id;

                $record = new RoleHasAction;
                $record->fromArray($this->_em, $record_info);

                $this->_em->persist($record);
            }

            $this->_em->flush();
        }

    }
}