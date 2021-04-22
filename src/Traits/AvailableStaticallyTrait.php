<?php

namespace App\Traits;

trait AvailableStaticallyTrait
{
    /** @var static */
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance(): static
    {
        return self::$instance;
    }

    /**
     */
    public static function hasInstance(): bool
    {
        return isset(self::$instance);
    }

    /**
     * @param static $instance
     */
    public static function setInstance($instance): void
    {
        self::$instance = $instance;
    }
}
