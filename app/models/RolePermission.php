<?php
namespace Entity;

/**
 * @Table(name="role_permissions", uniqueConstraints={
 *   @UniqueConstraint(name="role_permission_unique_idx", columns={"role_id","action_name","station_id"})
 * })
 * @Entity(repositoryClass="Entity\Repository\RolePermissionRepository")
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
     * @ManyToOne(targetEntity="Station", inversedBy="permissions")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}