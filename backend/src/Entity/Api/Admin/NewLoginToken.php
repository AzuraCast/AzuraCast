<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Enums\LoginTokenTypes;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(
        schema: 'Api_Admin_NewLoginToken',
        required: [
            'user',
        ],
        type: 'object',
    )
]
final class NewLoginToken
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

    #[OA\Property]
    public ?LoginTokenTypes $type = null;

    #[OA\Property(example: "SSO Login")]
    public ?string $comment = null;

    #[OA\Property]
    public int $expires_minutes = 30;
}
