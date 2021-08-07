<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'role_permissions'),
    ORM\UniqueConstraint(name: 'role_permission_unique_idx', columns: ['role_id', 'action_name', 'station_id'])
]
class RolePermission implements
    JsonSerializable,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\Column]
    protected int $role_id;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Role $role;

    #[ORM\Column(length: 50)]
    protected string $action_name;

    #[ORM\Column(nullable: true)]
    protected ?int $station_id = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Station $station = null;

    public function __construct(Role $role, Station $station = null, ?string $action_name = null)
    {
        $this->role = $role;
        $this->station = $station;

        if (null !== $action_name) {
            $this->setActionName($action_name);
        }
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function setStation(?Station $station): void
    {
        $this->station = $station;
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
