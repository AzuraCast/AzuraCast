<?php

declare(strict_types=1);

namespace App\Utilities;

use Monolog\Logger as MonologLogger;
use Monolog\Registry;

final class Logger
{
    public static function getInstance(): MonologLogger
    {
        return Registry::getInstance('app');
    }
}
