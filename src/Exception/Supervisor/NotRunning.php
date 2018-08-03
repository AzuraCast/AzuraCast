<?php
namespace App\Exception\Supervisor;

use Monolog\Logger;

class NotRunning extends \App\Exception\Supervisor
{
    protected $logger_level = Logger::INFO;
}
