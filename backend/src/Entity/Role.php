<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'role'),
    Attributes\Auditable
]
final class Role implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        ORM\Column(length: 100),
        Assert\NotBlank
    ]
    public string $name {
        set => $this->truncateString($value, 100);
    }

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    public private(set) Collection $users;

    /** @var Collection<int, RolePermission> */
    #[ORM\OneToMany(targetEntity: RolePermission::class, mappedBy: 'role')]
    public private(set) Collection $permissions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function __clone(): void
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
