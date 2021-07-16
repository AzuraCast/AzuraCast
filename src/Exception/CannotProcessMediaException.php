<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class CannotProcessMediaException extends Exception
{
    protected ?string $path = null;

    public function __construct(
        string $message = 'Cannot process media file.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::WARNING
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getMessageWithPath(): string
    {
        return sprintf(
            'Cannot process media file at path "%s": %s',
            $this->path,
            $this->message
        );
    }

    public static function forPath(string $path, string $error = 'General Error'): self
    {
        $exception = new self($error);
        $exception->setPath(basename($path));
        return $exception;
    }
}
