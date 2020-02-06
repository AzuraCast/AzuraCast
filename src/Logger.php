<?php
namespace App;

use Psr\Log\LoggerInterface;

class Logger
{
    /** @var LoggerInterface */
    protected static $instance;

    /**
     * @return LoggerInterface
     */
    public static function getInstance(): LoggerInterface
    {
        return self::$instance;
    }

    /**
     * @param LoggerInterface $instance
     */
    public static function setInstance(LoggerInterface $instance): void
    {
        self::$instance = $instance;
    }
}