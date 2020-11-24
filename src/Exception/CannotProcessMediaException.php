<?php

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class CannotProcessMediaException extends Exception
{
    public function __construct(
        string $message = 'Cannot process media file.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::WARNING
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    public static function forPath(string $path, string $error = 'General Error'): self
    {
        return new self(sprintf(
            'Cannot process media file at path "%s": %s',
            pathinfo($path, PATHINFO_FILENAME),
            $error
        ));
    }
}
