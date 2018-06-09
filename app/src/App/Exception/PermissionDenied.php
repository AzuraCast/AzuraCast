<?php
namespace App\Exception;

use Monolog\Logger;

class PermissionDenied extends \App\Exception
{
    protected $logger_level = Logger::INFO;
}