<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_AuditLogChangeset',
    required: ['*'],
    type: 'object'
)]
final readonly class AuditLogChangeset
{
    public function __construct(
        #[OA\Property]
        public string $field,
        #[OA\Property]
        public string $from,
        #[OA\Property]
        public string $to
    ) {
    }
}
