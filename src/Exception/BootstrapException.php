<?php
namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class BootstrapException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::ALERT
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}