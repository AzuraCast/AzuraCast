<?php

declare(strict_types=1);

namespace App\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum FlashLevels: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'danger';
    case Info = 'info';
}
