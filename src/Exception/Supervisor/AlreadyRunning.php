<?php
namespace App\Exception\Supervisor;

use Monolog\Logger;

class AlreadyRunning extends \AzuraCast\Exception\Supervisor
{
    protected $logger_level = Logger::INFO;
}
