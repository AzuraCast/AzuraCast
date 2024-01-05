<?php

declare(strict_types=1);

namespace App\Exception\Supervisor;

use App\Exception\SupervisorException;
use Monolog\Level;
use Throwable;

final class NotRunningException extends SupervisorException
{
    public function __construct(
        string $message = 'Process was not running yet.',
        int $code = 0,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
