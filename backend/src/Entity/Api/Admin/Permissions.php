<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Permissions',
    required: ['*'],
    type: 'object'
)]
final readonly class Permissions
{
    /**
     * @param Permission[] $global
     * @param Permission[] $station
     */
    public function __construct(
        #[OA\Property(
            items: new OA\Items(ref: '#/components/schemas/Api_Admin_Permission'),
        )]
        public array $global,
        #[OA\Property(
            items: new OA\Items(ref: '#/components/schemas/Api_Admin_Permission'),
        )]
        public array $station,
    ) {
    }
}
