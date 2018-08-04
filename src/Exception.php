<?php
namespace App;

use Monolog\Logger;

class Exception extends \Exception
{
    /** @var int The logging severity of the exception. */
    protected $logger_level = Logger::ERROR;

    /** @var array Any additional data that can be displayed in debugging. */
    protected $extra_data = [];

    /** @var array Additional data supplied to the logger class when handling the exception. */
    protected $logging_context = [];

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setLoggerLevel($logger_level): void
    {
        $this->logger_level = $logger_level;
    }

    public function getLoggerLevel(): int
    {
        return $this->logger_level;
    }

    public function addExtraData($legend, $data)
    {
        if (is_array($data)) {
            $this->extra_data[$legend] = $data;
        }
    }

    public function getExtraData(): array
    {
        return $this->extra_data;
    }

    public function addLoggingContext($key, $data)
    {
        $this->logging_context[$key] = $data;
    }

    public function getLoggingContext(): array
    {
        return $this->logging_context;
    }
}