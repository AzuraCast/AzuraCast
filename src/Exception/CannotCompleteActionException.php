<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class CannotCompleteActionException extends Exception
{
    public function __construct(
        string $message = 'Cannot complete action.',
        int $code = 500,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    public static function submitRequest(string $reason): self
    {
        return new self(
            sprintf(
                __('Cannot submit request: %s'),
                $reason
            )
        );
    }
}
