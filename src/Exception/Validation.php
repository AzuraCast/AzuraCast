<?php
namespace App\Exception;

use Monolog\Logger;

class Validation extends \Azura\Exception
{
    protected $logger_level = Logger::INFO;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Submission could not be validated.';
        }

        parent::__construct($message, $code, $previous);
    }
}
