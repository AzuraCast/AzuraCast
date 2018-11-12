<?php
namespace App\Exception;

use Monolog\Logger;
use Throwable;

class NotLoggedIn extends \Azura\Exception
{
    protected $logger_level = Logger::INFO;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Not Logged In';
        }

        parent::__construct($message, $code, $previous);
    }
}
