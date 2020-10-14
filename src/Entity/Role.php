<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="role")
 * @ORM\Entity
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class Role implements JsonSerializable
{
    use Traits\TruncateStrings;

    public const SUPER_ADMINISTRATOR_ROLE_ID = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @OA\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     * @OA\Property(example="Super Administrator")
     * @Assert\NotBlank
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles")
     * @var Collection
     */
    protected $users;

    /**
     * @ORM\OneToMany(targetEntity="RolePermission", mappedBy="role")
     * @OA\Property(@OA\Items)
     * @var Collection
     */
    protected $permissions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): int
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
