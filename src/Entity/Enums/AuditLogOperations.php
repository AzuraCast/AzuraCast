<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum AuditLogOperations: int
{
    case Insert = 1;
    case Update = 2;
    case Delete = 3;

    public function getName(): string
    {
        return match ($this) {
            self::Update => 'update',
            self::Delete => 'delete',
            self::Insert => 'insert'
        };
    }
}
