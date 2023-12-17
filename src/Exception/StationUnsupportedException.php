<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class StationUnsupportedException extends Exception
{
    public function __construct(
        string $message = 'This feature is not currently supported on this station.',
        int $code = 0,
        Throwable $previous = null,
        Level $loggerLevel = Level::Info
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    public static function generic(): self
    {
        return new self(
            __('This station does not currently support this functionality.')
        );
    }

    public static function onDemand(): self
    {
        return new self(
            __('This station does not currently support on-demand media.')
        );
    }

    public static function requests(): self
    {
        return new self(
            __('This station does not currently accept requests.')
        );
    }
}
