<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum LoginTokenTypes: string
{
    case ResetPassword = 'reset_password';
    case Login = 'login';

    public static function default(): self
    {
        return self::ResetPassword;
    }
}
