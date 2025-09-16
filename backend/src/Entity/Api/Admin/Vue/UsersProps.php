<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Vue_UsersProps',
    required: ['*'],
    type: 'object'
)]
final readonly class UsersProps
{
    public function __construct(
        #[OA\Property(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )]
        public array $roles,
    ) {
    }
}
