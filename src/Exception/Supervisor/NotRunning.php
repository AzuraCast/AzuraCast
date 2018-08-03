<?php
namespace App\Exception\Supervisor;

use Monolog\Logger;

class NotRunning extends \AzuraCast\Exception\Supervisor
{
    protected $logger_level = Logger::INFO;
}
