<?php
namespace App;

use Psr\Log\LogLevel;
use Throwable;

class Exception extends \Exception
{
    /** @var string The logging severity of the exception. */
    protected $loggerLevel;

    /** @var array Any additional data that can be displayed in debugging. */
    protected $extraData = [];

    /** @var array Additional data supplied to the logger class when handling the exception. */
    protected $loggingContext = [];

    /** @var string|null */
    protected $formattedMessage;

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
    public function setMessage($message): void
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
    public function setFormattedMessage($message): void
    {
        $this->formattedMessage = $message;
    }

    /**
     * @return string
     */
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
     * @param string|int $legend
     * @param mixed $data
     */
    public function addExtraData($legend, $data): void
    {
        if (is_array($data)) {
            $this->extraData[$legend] = $data;
        }
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param string|int $key
     * @param mixed $data
     */
    public function addLoggingContext($key, $data): void
    {
        $this->loggingContext[$key] = $data;
    }

    /**
     * @return array
     */
    public function getLoggingContext(): array
    {
        return $this->loggingContext;
    }
}