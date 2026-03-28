<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Traits\HasLinks;
use App\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_UserWithDetails',
    required: ['*'],
    type: 'object'
)]
final class UserWithDetails extends User
{
    use HasLinks;

    #[OA\Property(
        description: 'Whether this user record represents the currently logged-in user.',
        example: true
    )]
    public bool $is_me;
}
