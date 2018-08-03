<?php
namespace App\Exception;

use Monolog\Logger;

class NotFound extends \App\Exception
{
    protected $logger_level = Logger::INFO;
}