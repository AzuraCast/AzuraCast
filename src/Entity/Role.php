<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Annotations as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/** @OA\Schema(type="object") */
#[
    ORM\Entity,
    ORM\Table(name: 'role'),
    AuditLog\Auditable
]
class Role implements JsonSerializable, Stringable
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const SUPER_ADMINISTRATOR_ROLE_ID = 1;

    /** @OA\Property(example="Super Administrator") */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    protected string $name;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    protected Collection $users;

    /** @OA\Property(type="array", @OA\Items) */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: RolePermission::class)]
    protected Collection $permissions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $this->truncateString($name, 100);
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $return = [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => [
                'global' => [],
                'station' => [],
            ],
        ];

        foreach ($this->permissions as $permission) {
            /** @var RolePermission $permission */

            if ($permission->hasStation()) {
                $return['permissions']['station'][$permission->getStation()->getId()][] = $permission->getActionName();
            } else {
                $return['permissions']['global'][] = $permission->getActionName();
            }
        }

        return $return;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
