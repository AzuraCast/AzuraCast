<?php

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class StationUnsupportedException extends Exception
{
    public function __construct(
        string $message = 'This feature is not currently supported on this station.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
