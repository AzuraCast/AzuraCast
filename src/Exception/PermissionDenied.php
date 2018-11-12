<?php
namespace App\Exception;

use Monolog\Logger;

class PermissionDenied extends \Azura\Exception
{
    protected $logger_level = Logger::INFO;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Permission Denied';
        }

        parent::__construct($message, $code, $previous);
    }
}
