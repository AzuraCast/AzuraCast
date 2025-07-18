<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Traits\HasLinks;
use App\Entity\Role as RoleEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Role',
    required: ['name'],
    type: 'object'
)]
final class Role
{
    use HasLinks;

    public function __construct(
        #[OA\Property(
            readOnly: true
        )]
        public readonly int $id,
        #[OA\Property(
            example: "Super Administrator"
        )]
        public readonly string $name,
        #[OA\Property]
        public readonly RolePermissions $permissions,
    ) {
    }

    #[OA\Property(
        description: 'Whether this role is the protected "Super Administrator" role.',
        readOnly: true
    )]
    public bool $is_super_admin;


    public static function fromRole(RoleEntity $role): self
    {
        return new self(
            $role->id,
            $role->name,
            RolePermissions::fromRolePermissions($role->permissions)
        );
    }
}
