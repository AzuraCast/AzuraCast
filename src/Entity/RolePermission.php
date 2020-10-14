<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Table(name="role_permissions", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="role_permission_unique_idx", columns={"role_id","action_name","station_id"})
 * })
 * @ORM\Entity()
 */
class RolePermission implements JsonSerializable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="role_id", type="integer")
     * @var int
     */
    protected $role_id;

    /**
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Role
     */
    protected $role;

    /**
     * @ORM\Column(name="action_name", type="string", length=50, nullable=false)
     * @var string
     */
    protected $action_name;

    /**
     * @ORM\Column(name="station_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station|null
     */
    protected $station;

    public function __construct(Role $role, Station $station = null, $action_name = null)
    {
        $this->role = $role;
        $this->station = $station;

        if (null !== $action_name) {
            $this->setActionName($action_name);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function hasStation(): bool
    {
        return (null !== $this->station);
    }

    public function getActionName(): string
    {
        return $this->action_name;
    }

    public function setActionName(string $action_name): void
    {
        $this->action_name = $action_name;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'action' => $this->action_name,
            'station_id' => $this->station_id,
        ];
    }
}
