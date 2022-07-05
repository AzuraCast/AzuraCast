<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

final class NotFoundException extends Exception
{
    public function __construct(
        string $message = 'Record not found.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::DEBUG
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
