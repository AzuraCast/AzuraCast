<?php

declare(strict_types=1);

namespace App\Service;

use App\Environment;
use Redis;
use RuntimeException;

final class RedisFactory
{
    public function __construct(
        private readonly Environment $environment
    ) {
    }

    public function isSupported(): bool
    {
        return !$this->environment->isTesting() && $this->environment->enableRedis();
    }

    public function createInstance(): Redis
    {
        if (!$this->isSupported()) {
            throw new RuntimeException('Redis is disabled on this installation.');
        }

        $settings = $this->environment->getRedisSettings();

        $redis = new Redis();
        if (isset($settings['socket'])) {
            $redis->connect($settings['socket']);
        } else {
            $redis->connect($settings['host'], $settings['port'], 15);
        }
        $redis->select($settings['db']);

        return $redis;
    }
}
