<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class InvalidRequestAttribute extends Exception
{
    public function __construct(
        string $message = 'Invalid request attribute.',
        int $code = 500,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
