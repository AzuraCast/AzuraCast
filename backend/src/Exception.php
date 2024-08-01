<?php

declare(strict_types=1);

namespace App;

use Exception as PhpException;
use Monolog\Level;
use Throwable;

class Exception extends PhpException
{
    /** @var array Any additional data that can be displayed in debugging. */
    protected array $extraData = [];

    /** @var array Additional data supplied to the logger class when handling the exception. */
    protected array $loggingContext = [];

    protected ?string $formattedMessage;

    public function __construct(
        string $message = '',
        int $code = 0,
        Throwable $previous = null,
        protected Level $loggerLevel = Level::Error
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string A display-formatted message, if one exists, or
     *                the regular message if one doesn't.
     */
    public function getFormattedMessage(): string
    {
        return $this->formattedMessage ?? $this->message;
    }

    /**
     * Set a display-formatted message (if one exists).
     */
    public function setFormattedMessage(?string $message): void
    {
        $this->formattedMessage = $message;
    }

    public function getLoggerLevel(): Level
    {
        return $this->loggerLevel;
    }

    public function setLoggerLevel(Level $loggerLevel): void
    {
        $this->loggerLevel = $loggerLevel;
    }

    public function addExtraData(int|string $legend, mixed $data): void
    {
        if (is_array($data)) {
            $this->extraData[$legend] = $data;
        }
    }

    /**
     * @return mixed[]
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function addLoggingContext(int|string $key, mixed $data): void
    {
        $this->loggingContext[$key] = $data;
    }

    public function getLoggingContext(): array
    {
        return $this->loggingContext;
    }
}
