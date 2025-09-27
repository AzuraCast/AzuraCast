<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_SsoTokenDetails',
    type: 'object'
)]
final readonly class SsoTokenDetails
{
    public function __construct(
        #[OA\Property(
            description: 'The unique identifier of the SSO token.',
            example: 'abc123def456'
        )]
        public string $token_id,
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
            description: 'The complete SSO URL that can be used to log in.',
            example: 'https://your-azuracast.com/sso/login?token=abc123def456:verifier'
        )]
        public string $sso_url,
        #[OA\Property(
            description: 'The user this token is for.',
            ref: '#/components/schemas/Api_Admin_SsoUser'
        )]
        public object $user
    ) {
    }
}
