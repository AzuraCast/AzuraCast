<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class CsrfValidationException extends Exception
{
    public function __construct(
        string $message = 'CSRF Validation Error',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
