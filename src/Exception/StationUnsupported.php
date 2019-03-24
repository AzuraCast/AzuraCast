<?php
namespace App\Exception;

use Monolog\Logger;

class StationUnsupported extends \Azura\Exception
{
    protected $logger_level = Logger::INFO;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'This feature is not currently supported on this station.';
        }

        parent::__construct($message, $code, $previous);
    }
}
