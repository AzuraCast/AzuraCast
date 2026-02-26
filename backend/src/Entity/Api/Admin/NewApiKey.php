<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(
        schema: 'Api_Admin_NewApiKey',
        required: [
            'user',
        ],
        type: 'object',
    )
]
final class NewApiKey
{
    #[Assert\NotBlank]
    #[OA\Property(
        description: 'User ID or e-mail address.',
        anyOf: [
            new OA\Schema(type: 'integer', format: 'int64'),
            new OA\Schema(type: 'string'),
        ]
    )]
    public string|int $user;

    #[OA\Property(example: 'Admin-generated API key')]
    public string $comment = '';
}
