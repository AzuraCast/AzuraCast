<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enums\PermissionInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'role_permissions'),
    ORM\UniqueConstraint(name: 'role_permission_unique_idx', columns: ['role_id', 'action_name', 'station_id'])
]
final class RolePermission implements
    JsonSerializable,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly Role $role;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(insertable: false, updatable: false)]
    public private(set) int $role_id;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public ?Station $station;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $station_id = null;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    #[ORM\Column(length: 50)]
    public readonly string $action_name;


    public function __construct(
        Role $role,
        ?Station $station,
        string|PermissionInterface $actionName
    ) {
        $this->role = $role;
        $this->station = $station;

        if ($actionName instanceof PermissionInterface) {
            $actionName = $actionName->getValue();
        }

        $this->action_name = $this->truncateString($actionName, 50);
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'action'     => $this->action_name,
            'station_id' => $this->station?->id,
        ];
    }
}
