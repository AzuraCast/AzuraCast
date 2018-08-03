<?php
namespace App\Entity;

/**
 * @Table(name="role_permissions", uniqueConstraints={
 *   @UniqueConstraint(name="role_permission_unique_idx", columns={"role_id","action_name","station_id"})
 * })
 * @Entity(repositoryClass="Entity\Repository\RolePermissionRepository")
 */
class RolePermission
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="role_id", type="integer")
     * @var int
     */
    protected $role_id;

    /**
     * @ManyToOne(targetEntity="Role", inversedBy="permissions")
     * @JoinColumns({
     *   @JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Role
     */
    protected $role;

    /**
     * @Column(name="action_name", type="string", length=50, nullable=false)
     * @var string
     */
    protected $action_name;

    /**
     * @Column(name="station_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="permissions")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station|null
     */
    protected $station;

    /**
     * RolePermission constructor.
     * @param Role $role
     * @param Station|null $station
     */
    public function __construct(Role $role, Station $station = null)
    {
        $this->role = $role;
        $this->station = $station;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return $this->role;
    }

    /**
     * @return Station|null
     */
    public function getStation()
    {
        return $this->station;
    }

    /**
     * @return string
     */
    public function getActionName(): string
    {
        return $this->action_name;
    }

    /**
     * @param string $action_name
     */
    public function setActionName(string $action_name)
    {
        $this->action_name = $action_name;
    }
}
