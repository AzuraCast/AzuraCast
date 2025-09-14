<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use Redis;
use RuntimeException;

final class RedisFactory
{
    use EnvironmentAwareTrait;

    protected ?Redis $instance = null;

    public function isSupported(): bool
    {
        return !$this->environment->isTesting() && $this->environment->enableRedis();
    }

    public function getInstance(): Redis
    {
        if (!$this->isSupported()) {
            throw new RuntimeException('Redis is disabled on this installation.');
        }

        if (null === $this->instance) {
            $settings = $this->environment->getRedisSettings();

            $this->instance = new Redis();
            if (isset($settings['socket'])) {
                $this->instance->connect($settings['socket']);
            } else {
                $this->instance->connect($settings['host'], $settings['port'], 15);
            }

            if (0 !== $settings['db']) {
                $this->instance->select($settings['db']);
            }
        }

        return $this->instance;
    }
}
