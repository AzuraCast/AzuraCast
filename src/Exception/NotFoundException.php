<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class NotFoundException extends Exception
{
    public function __construct(
        string $message = 'Record not found.',
        int $code = 0,
        Throwable $previous = null,
        Level $loggerLevel = Level::Debug
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    public static function generic(): self
    {
        return new self(__('Record not found.'));
    }

    public static function file(): self
    {
        return new self(__('File not found.'));
    }

    public static function station(): self
    {
        return new self(__('Station not found.'));
    }

    public static function podcast(): self
    {
        return new self(__('Podcast not found.'));
    }
}
