<?php
namespace App\Exception;

use Psr\Log\LogLevel;
use Throwable;

class NotLoggedIn extends \Azura\Exception
{
    public function __construct(
        string $message = 'Not logged in.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::DEBUG
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
}
