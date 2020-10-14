<?php

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class InvalidRequestAttribute extends Exception
{
    public function __construct(
        string $message = 'Invalid request attribute.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::DEBUG
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
