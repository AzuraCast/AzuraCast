<?php
namespace App\Exception;

use Monolog\Logger;

class Bootstrap extends \App\Exception
{
    protected $logger_level = Logger::ALERT;
}