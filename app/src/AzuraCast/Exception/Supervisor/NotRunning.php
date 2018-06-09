<?php
namespace AzuraCast\Exception\Supervisor;

use Monolog\Logger;

class NotRunning extends \AzuraCast\Exception\Supervisor
{
    protected $logger_level = Logger::INFO;
}