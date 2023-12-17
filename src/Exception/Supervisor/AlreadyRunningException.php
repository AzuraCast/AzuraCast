<?php

declare(strict_types=1);

namespace App\Exception\Supervisor;

use App\Exception\SupervisorException;
use Monolog\Level;
use Throwable;

final class AlreadyRunningException extends SupervisorException
{
    public function __construct(
        string $message = 'Process was already running.',
        int $code = 0,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
