<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = 'You have exceeded the rate limit for this application.',
        int $code = 0,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
