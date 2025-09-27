<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_SsoTokenResponse',
    type: 'object'
)]
final readonly class SsoTokenResponse
{
    public function __construct(
        #[OA\Property(
            description: 'Whether the request was successful.',
            example: true
        )]
        public bool $success,
        #[OA\Property(
            description: 'The response data.',
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/Api_Admin_SsoTokenDetails'),
                new OA\Schema(ref: '#/components/schemas/Api_Admin_SsoTokenList'),
                new OA\Schema(type: 'array', items: new OA\Items(ref: '#/components/schemas/Api_Admin_SsoTokenList')),
                new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'revoked_count', type: 'integer', example: 3),
                        new OA\Property(property: 'cleaned_count', type: 'integer', example: 5),
                    ]
                ),
            ]
        )]
        public mixed $data = null,
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
