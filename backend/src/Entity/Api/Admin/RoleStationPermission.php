<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Enums\StationPermissions;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_RoleStationPermission',
    required: ['*'],
    type: 'object'
)]
final readonly class RoleStationPermission
{
    /**
     * @param int $id
     * @param string[] $permissions
     */
    public function __construct(
        #[OA\Property(description: 'The station ID.')]
        public int $id,
        #[OA\Property(
            items: new OA\Items(
                type: StationPermissions::class,
            )
        )]
        public array $permissions,
    ) {
    }
}
