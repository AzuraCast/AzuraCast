<?php
namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class LockAlreadyExistsException extends Exception
{
    public function __construct(
        string $message = 'Lock already exists.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::ERROR
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}