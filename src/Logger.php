<?php
namespace App;

use Psr\Log\LoggerInterface;

class Logger
{
    protected static LoggerInterface $instance;

    public static function getInstance(): LoggerInterface
    {
        return self::$instance;
    }

    public static function setInstance(LoggerInterface $instance): void
    {
        self::$instance = $instance;
    }
}