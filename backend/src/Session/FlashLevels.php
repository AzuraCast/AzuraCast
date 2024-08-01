<?php

declare(strict_types=1);

namespace App\Session;

enum FlashLevels: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'danger';
    case Info = 'info';
}
