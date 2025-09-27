<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_SsoUser',
    type: 'object'
)]
final readonly class SsoUser
{
    public function __construct(
        #[OA\Property(
            description: 'The unique identifier of the user.',
            example: 123
        )]
        public int $id,
        #[OA\Property(
            description: 'The email address of the user.',
            example: 'user@example.com'
        )]
        public string $email,
        #[OA\Property(
            description: 'The display name of the user.',
            example: 'John Doe',
            nullable: true
        )]
        public ?string $name = null
    ) {
    }
}
