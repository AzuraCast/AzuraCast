<?php

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class StationNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Station not found.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
