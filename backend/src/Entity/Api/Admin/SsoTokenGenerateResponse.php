<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_SsoTokenGenerateResponse',
    type: 'object'
)]
final readonly class SsoTokenGenerateResponse
{
    public function __construct(
        #[OA\Property(
            description: 'Whether the request was successful.',
            example: true
        )]
        public bool $success,
        #[OA\Property(
            description: 'The generated SSO token details.',
            ref: '#/components/schemas/Api_Admin_SsoTokenDetails'
        )]
        public object $data,
        #[OA\Property(
            description: 'Error message if the request failed.',
            example: 'User not found',
            nullable: true
        )]
        public ?string $error = null,
        #[OA\Property(
            description: 'Validation error details if the request failed.',
            type: 'object',
            nullable: true
        )]
        public ?array $details = null
    ) {
    }
}
