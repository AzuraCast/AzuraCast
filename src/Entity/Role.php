<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** @OA\Schema(type="object") */
#[ORM\Entity, ORM\Table(name: 'role')]
#[AuditLog\Auditable]
class Role implements JsonSerializable
{
    use Traits\TruncateStrings;

    public const SUPER_ADMINISTRATOR_ROLE_ID = 1;

    /** @OA\Property(example=1) */
    #[ORM\Column(nullable: false)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id;

    /** @OA\Property(example="Super Administrator") */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    protected string $name;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    protected Collection $users;

    /** @OA\Property(@OA\Items) */
    #[ORM\OneToMany(targetEntity: RolePermission::class, mappedBy: 'role')]
    protected Collection $permissions;

    #[Pure] public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @AuditLog\AuditIdentifier()
     */
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
}
