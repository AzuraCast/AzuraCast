<?php
namespace App\Exception;

use Monolog\Logger;

class CsrfValidation extends \App\Exception
{
    protected $logger_level = Logger::INFO;
}