<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'role'),
    Attributes\Auditable
]
class Role implements JsonSerializable, Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property(example: "Super Administrator"),
        ORM\Column(length: 100),
        Assert\NotBlank
    ]
    protected string $name;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    protected Collection $users;

    /** @var Collection<int, RolePermission> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(mappedBy: 'role', targetEntity: RolePermission::class)
    ]
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, RolePermission>
     */
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

            $station = $permission->getStation();
            if (null !== $station) {
                $return['permissions']['station'][$station->getIdRequired()][] = $permission->getActionName();
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
