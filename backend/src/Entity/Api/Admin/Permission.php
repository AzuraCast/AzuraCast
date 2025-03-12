<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Permission',
    required: ['*'],
    type: 'object'
)]
final readonly class Permission
{
    public function __construct(
        #[OA\Property]
        public string $id,
        #[OA\Property]
        public string $name,
    ) {
    }
}
