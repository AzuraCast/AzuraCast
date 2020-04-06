<?php
namespace App\Lock;

use Psr\Log\LoggerInterface;
use Redis;

class LockManager
{
    protected Redis $redis;

    protected LoggerInterface $logger;

    public function __construct(Redis $redis, LoggerInterface $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function getLock(
        string $key,
        int $ttl = 30,
        bool $waitForLock = false,
        ?int $waitTimeout = null
    ): Lock {
        return new Lock($this->redis, $this->logger, $key, $ttl, $waitForLock, $waitTimeout);
    }
}