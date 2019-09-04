<?php
namespace App\Exception;

use Azura\Exception;
use Psr\Log\LogLevel;
use Throwable;

class OutOfSpace extends Exception
{
    public function __construct(
        string $message = 'Out of available space.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
