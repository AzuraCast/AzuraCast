<?php

declare(strict_types=1);

namespace App\Enums;

enum ListFilterMode: string
{
    case Equals = 'equals';
    case Contains = 'contains';
}
