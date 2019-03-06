<?php
namespace App\Exception;

use Monolog\Logger;

class NotFound extends \Azura\Exception
{
    protected $logger_level = Logger::DEBUG;
}
