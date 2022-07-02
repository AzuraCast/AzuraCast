<?php

declare(strict_types=1);

namespace App\Exception\Supervisor;

use App\Exception\SupervisorException;
use Psr\Log\LogLevel;
use Throwable;

final class AlreadyRunningException extends SupervisorException
{
    public function __construct(
        string $message = 'Process was already running.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
