<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class NotLoggedInException extends Exception
{
    public function __construct(
        string $message = 'Not logged in.',
        int $code = 0,
        Throwable $previous = null,
        Level $loggerLevel = Level::Debug
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
