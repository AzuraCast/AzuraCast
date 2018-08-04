<?php
namespace App\Exception;

use Monolog\Logger;

class NotLoggedIn extends \App\Exception
{
    protected $logger_level = Logger::INFO;
}