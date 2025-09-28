<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\RolePermission;
use App\Enums\GlobalPermissions;
use Doctrine\Common\Collections\Collection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_RolePermissions',
    required: ['*'],
    type: 'object'
)]
final readonly class RolePermissions
{
    /**
     * @param string[] $global
     * @param RoleStationPermission[] $station
     */
    public function __construct(
        #[OA\Property(
            items: new OA\Items(
                type: GlobalPermissions::class,
            ),
        )]
        public array $global,
        #[OA\Property(
            items: new OA\Items(ref: '#/components/schemas/Api_Admin_RoleStationPermission'),
        )]
        public array $station,
    ) {
    }

    /**
     * @param Collection<RolePermission> $rolePermissions
     * @return self
     */
    public static function fromRolePermissions(Collection $rolePermissions): self
    {
        $globalPermissions = [];
        $stationPermissions = [];

        $permissionsByStation = [];

        foreach ($rolePermissions as $permission) {
            $station = $permission->station;
            if (null !== $station) {
                $permissionsByStation[$station->id][] = $permission->action_name;
            } else {
                $globalPermissions[] = $permission->action_name;
            }
        }

        foreach ($permissionsByStation as $stationId => $stationPerms) {
            $stationPermissions[] = new RoleStationPermission(
                $stationId,
                $stationPerms
            );
        }

        return new self(
            $globalPermissions,
            $stationPermissions
        );
    }
}
