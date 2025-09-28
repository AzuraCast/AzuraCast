<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

use App\Entity\Api\Admin\RolePermissions;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Vue_UserGlobals',
    required: ['*'],
    type: 'object'
)]
final readonly class UserGlobals
{
    public function __construct(
        #[OA\Property]
        public int $id,
        #[OA\Property]
        public ?string $displayName,
        #[OA\Property]
        public RolePermissions $permissions,
    ) {
    }
}
