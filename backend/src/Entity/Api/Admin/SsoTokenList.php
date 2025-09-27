<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_SsoTokenList',
    type: 'object'
)]
final readonly class SsoTokenList
{
    public function __construct(
        #[OA\Property(
            description: 'The unique identifier of the SSO token.',
            example: 'abc123def456'
        )]
        public string $id,
        #[OA\Property(
            description: 'Comment describing the token.',
            example: 'WHMCS SSO Login'
        )]
        public string $comment,
        #[OA\Property(
            description: 'When the token was created (Unix timestamp).',
            example: 1640995200
        )]
        public int $created_at,
        #[OA\Property(
            description: 'When the token expires (Unix timestamp).',
            example: 1640998800
        )]
        public int $expires_at,
        #[OA\Property(
            description: 'How many seconds until the token expires.',
            example: 300
        )]
        public int $expires_in,
        #[OA\Property(
            description: 'Whether the token is still valid (not used and not expired).',
            example: true
        )]
        public bool $is_valid,
        #[OA\Property(
            description: 'IP address that created the token (if available).',
            example: '192.168.1.100',
            nullable: true
        )]
        public ?string $ip_address = null
    ) {
    }
}
