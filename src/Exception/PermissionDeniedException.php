<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class PermissionDeniedException extends Exception
{
    public function __construct(
        string $message = 'Permission denied.',
        int $code = 403,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    public static function create(): self
    {
        return new self(
            __('You do not have permission to access this portion of the site.')
        );
    }
}
