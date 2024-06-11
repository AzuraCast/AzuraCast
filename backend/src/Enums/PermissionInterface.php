<?php

declare(strict_types=1);

namespace App\Enums;

interface PermissionInterface
{
    public function getValue(): string;

    public function needsStation(): bool;
}
