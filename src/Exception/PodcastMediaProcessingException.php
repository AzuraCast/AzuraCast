<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class PodcastMediaProcessingException extends Exception
{
    public function __construct(
        string $message = 'The podcast media provided could not be processed.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::ERROR
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
