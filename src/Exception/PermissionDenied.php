<?php
namespace App\Exception;

use Psr\Log\LogLevel;
use Throwable;

class PermissionDenied extends \Azura\Exception
{
    public function __construct(
        string $message = 'Permission denied.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
