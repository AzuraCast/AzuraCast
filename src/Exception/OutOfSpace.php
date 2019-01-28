<?php
namespace App\Exception;

use Monolog\Logger;

class OutOfSpace extends \Azura\Exception
{
    protected $logger_level = Logger::INFO;
}
