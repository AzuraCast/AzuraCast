<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Monolog\Level;
use Throwable;

final class DumpException extends Exception
{
    public function __construct(
        string $message = 'Debug app dump.',
        int $code = 200,
        Throwable $previous = null,
        Level $loggerLevel = Level::Debug,
        private readonly array $dumps = []
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    /**
     * @return string[]
     */
    public function getDumps(): array
    {
        return $this->dumps;
    }
}
