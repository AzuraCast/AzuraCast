<?php
namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class SessionNotReadyException extends Exception
{
    public function __construct(
        string $message = 'Session was used before it was initialized by middleware.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::CRITICAL
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
