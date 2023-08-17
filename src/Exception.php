<?php

declare(strict_types=1);

namespace App;

use Exception as PhpException;
use Psr\Log\LogLevel;
use Throwable;

class Exception extends PhpException
{
    /** @var string The logging severity of the exception. */
    protected string $loggerLevel;

    /** @var array Any additional data that can be displayed in debugging. */
    protected array $extraData = [];

    /** @var array Additional data supplied to the logger class when handling the exception. */
    protected array $loggingContext = [];

    /** @var string|null */
    protected ?string $formattedMessage;

    public function __construct(
        string $message = '',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::ERROR
    ) {
        parent::__construct($message, $code, $previous);

        $this->loggerLevel = $loggerLevel;
    }

    /**
     * @param string $message
     */
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
     *
     * @param string|null $message
     */
    public function setFormattedMessage(?string $message): void
    {
        $this->formattedMessage = $message;
    }

    public function getLoggerLevel(): string
    {
        return $this->loggerLevel;
    }

    /**
     * @param string $loggerLevel
     */
    public function setLoggerLevel(string $loggerLevel): void
    {
        $this->loggerLevel = $loggerLevel;
    }

    /**
     * @param int|string $legend
     * @param mixed $data
     */
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

    /**
     * @param int|string $key
     * @param mixed $data
     */
    public function addLoggingContext(int|string $key, mixed $data): void
    {
        $this->loggingContext[$key] = $data;
    }

    /**
     * @return mixed[]
     */
    public function getLoggingContext(): array
    {
        return $this->loggingContext;
    }
}
